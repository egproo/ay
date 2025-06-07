<?php
class ControllerPurchasePlanning extends Controller {
    private $error = array();

    /**
     * عرض صفحة تخطيط المشتريات
     */
    public function index() {
        $this->load->language('purchase/planning');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('purchase/planning');

        $this->getList();
    }

    /**
     * إضافة خطة شراء جديدة
     */
    public function add() {
        $this->load->language('purchase/planning');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('purchase/planning');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
            $plan_id = $this->model_purchase_planning->addPlan($this->request->post);

            $this->session->data['success'] = $this->language->get('text_success');

            $url = '';

            if (isset($this->request->get['filter_name'])) {
                $url .= '&filter_name=' . urlencode(html_entity_decode($this->request->get['filter_name'], ENT_QUOTES, 'UTF-8'));
            }

            if (isset($this->request->get['filter_status'])) {
                $url .= '&filter_status=' . $this->request->get['filter_status'];
            }

            if (isset($this->request->get['filter_period'])) {
                $url .= '&filter_period=' . $this->request->get['filter_period'];
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

            $this->response->redirect($this->url->link('purchase/planning', 'user_token=' . $this->session->data['user_token'] . $url, true));
        }

        $this->getForm();
    }

    /**
     * تعديل خطة شراء
     */
    public function edit() {
        $this->load->language('purchase/planning');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('purchase/planning');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
            $this->model_purchase_planning->editPlan($this->request->get['plan_id'], $this->request->post);

            $this->session->data['success'] = $this->language->get('text_success');

            $url = '';

            if (isset($this->request->get['filter_name'])) {
                $url .= '&filter_name=' . urlencode(html_entity_decode($this->request->get['filter_name'], ENT_QUOTES, 'UTF-8'));
            }

            if (isset($this->request->get['filter_status'])) {
                $url .= '&filter_status=' . $this->request->get['filter_status'];
            }

            if (isset($this->request->get['filter_period'])) {
                $url .= '&filter_period=' . $this->request->get['filter_period'];
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

            $this->response->redirect($this->url->link('purchase/planning', 'user_token=' . $this->session->data['user_token'] . $url, true));
        }

        $this->getForm();
    }

    /**
     * حذف خطة شراء
     */
    public function delete() {
        $this->load->language('purchase/planning');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('purchase/planning');

        if (isset($this->request->post['selected']) && $this->validateDelete()) {
            foreach ($this->request->post['selected'] as $plan_id) {
                $this->model_purchase_planning->deletePlan($plan_id);
            }

            $this->session->data['success'] = $this->language->get('text_success');

            $url = '';

            if (isset($this->request->get['filter_name'])) {
                $url .= '&filter_name=' . urlencode(html_entity_decode($this->request->get['filter_name'], ENT_QUOTES, 'UTF-8'));
            }

            if (isset($this->request->get['filter_status'])) {
                $url .= '&filter_status=' . $this->request->get['filter_status'];
            }

            if (isset($this->request->get['filter_period'])) {
                $url .= '&filter_period=' . $this->request->get['filter_period'];
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

            $this->response->redirect($this->url->link('purchase/planning', 'user_token=' . $this->session->data['user_token'] . $url, true));
        }

        $this->getList();
    }

    /**
     * عرض قائمة خطط الشراء
     */
    protected function getList() {
        if (isset($this->request->get['filter_name'])) {
            $filter_name = $this->request->get['filter_name'];
        } else {
            $filter_name = '';
        }

        if (isset($this->request->get['filter_status'])) {
            $filter_status = $this->request->get['filter_status'];
        } else {
            $filter_status = '';
        }

        if (isset($this->request->get['filter_period'])) {
            $filter_period = $this->request->get['filter_period'];
        } else {
            $filter_period = '';
        }

        if (isset($this->request->get['sort'])) {
            $sort = $this->request->get['sort'];
        } else {
            $sort = 'pp.plan_name';
        }

        if (isset($this->request->get['order'])) {
            $order = $this->request->get['order'];
        } else {
            $order = 'ASC';
        }

        if (isset($this->request->get['page'])) {
            $page = (int)$this->request->get['page'];
        } else {
            $page = 1;
        }

        $url = '';

        if (isset($this->request->get['filter_name'])) {
            $url .= '&filter_name=' . urlencode(html_entity_decode($this->request->get['filter_name'], ENT_QUOTES, 'UTF-8'));
        }

        if (isset($this->request->get['filter_status'])) {
            $url .= '&filter_status=' . $this->request->get['filter_status'];
        }

        if (isset($this->request->get['filter_period'])) {
            $url .= '&filter_period=' . $this->request->get['filter_period'];
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
            'href' => $this->url->link('purchase/planning', 'user_token=' . $this->session->data['user_token'] . $url, true)
        );

        $data['add'] = $this->url->link('purchase/planning/add', 'user_token=' . $this->session->data['user_token'] . $url, true);
        $data['delete'] = $this->url->link('purchase/planning/delete', 'user_token=' . $this->session->data['user_token'] . $url, true);

        $data['plans'] = array();

        $filter_data = array(
            'filter_name'   => $filter_name,
            'filter_status' => $filter_status,
            'filter_period' => $filter_period,
            'sort'          => $sort,
            'order'         => $order,
            'start'         => ($page - 1) * $this->config->get('config_limit_admin'),
            'limit'         => $this->config->get('config_limit_admin')
        );

        $plan_total = $this->model_purchase_planning->getTotalPlans($filter_data);

        $results = $this->model_purchase_planning->getPlans($filter_data);

        foreach ($results as $result) {
            $data['plans'][] = array(
                'plan_id'       => $result['plan_id'],
                'plan_name'     => $result['plan_name'],
                'plan_period'   => $result['plan_period'],
                'start_date'    => date($this->language->get('date_format_short'), strtotime($result['start_date'])),
                'end_date'      => date($this->language->get('date_format_short'), strtotime($result['end_date'])),
                'total_budget'  => $this->currency->format($result['total_budget'], $this->config->get('config_currency')),
                'used_budget'   => $this->currency->format($result['used_budget'], $this->config->get('config_currency')),
                'remaining_budget' => $this->currency->format($result['total_budget'] - $result['used_budget'], $this->config->get('config_currency')),
                'status'        => $result['status'],
                'status_class'  => $this->getStatusClass($result['status']),
                'progress'      => $result['total_budget'] > 0 ? round(($result['used_budget'] / $result['total_budget']) * 100, 2) : 0,
                'edit'          => $this->url->link('purchase/planning/edit', 'user_token=' . $this->session->data['user_token'] . '&plan_id=' . $result['plan_id'] . $url, true),
                'view'          => $this->url->link('purchase/planning/view', 'user_token=' . $this->session->data['user_token'] . '&plan_id=' . $result['plan_id'] . $url, true)
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

        $data['sort_name'] = $this->url->link('purchase/planning', 'user_token=' . $this->session->data['user_token'] . '&sort=pp.plan_name' . $url, true);
        $data['sort_period'] = $this->url->link('purchase/planning', 'user_token=' . $this->session->data['user_token'] . '&sort=pp.plan_period' . $url, true);
        $data['sort_budget'] = $this->url->link('purchase/planning', 'user_token=' . $this->session->data['user_token'] . '&sort=pp.total_budget' . $url, true);
        $data['sort_status'] = $this->url->link('purchase/planning', 'user_token=' . $this->session->data['user_token'] . '&sort=pp.status' . $url, true);

        $url = '';

        if (isset($this->request->get['filter_name'])) {
            $url .= '&filter_name=' . urlencode(html_entity_decode($this->request->get['filter_name'], ENT_QUOTES, 'UTF-8'));
        }

        if (isset($this->request->get['filter_status'])) {
            $url .= '&filter_status=' . $this->request->get['filter_status'];
        }

        if (isset($this->request->get['filter_period'])) {
            $url .= '&filter_period=' . $this->request->get['filter_period'];
        }

        if (isset($this->request->get['sort'])) {
            $url .= '&sort=' . $this->request->get['sort'];
        }

        if (isset($this->request->get['order'])) {
            $url .= '&order=' . $this->request->get['order'];
        }

        $pagination = new Pagination();
        $pagination->total = $plan_total;
        $pagination->page = $page;
        $pagination->limit = $this->config->get('config_limit_admin');
        $pagination->url = $this->url->link('purchase/planning', 'user_token=' . $this->session->data['user_token'] . $url . '&page={page}', true);

        $data['pagination'] = $pagination->render();

        $data['results'] = sprintf($this->language->get('text_pagination'), ($plan_total) ? (($page - 1) * $this->config->get('config_limit_admin')) + 1 : 0, ((($page - 1) * $this->config->get('config_limit_admin')) > ($plan_total - $this->config->get('config_limit_admin'))) ? $plan_total : ((($page - 1) * $this->config->get('config_limit_admin')) + $this->config->get('config_limit_admin')), $plan_total, ceil($plan_total / $this->config->get('config_limit_admin')));

        $data['filter_name'] = $filter_name;
        $data['filter_status'] = $filter_status;
        $data['filter_period'] = $filter_period;

        $data['sort'] = $sort;
        $data['order'] = $order;

        // حالات الخطة
        $data['statuses'] = array(
            array('value' => 'draft', 'text' => $this->language->get('text_status_draft')),
            array('value' => 'active', 'text' => $this->language->get('text_status_active')),
            array('value' => 'completed', 'text' => $this->language->get('text_status_completed')),
            array('value' => 'cancelled', 'text' => $this->language->get('text_status_cancelled'))
        );

        // فترات الخطة
        $data['periods'] = array(
            array('value' => 'monthly', 'text' => $this->language->get('text_period_monthly')),
            array('value' => 'quarterly', 'text' => $this->language->get('text_period_quarterly')),
            array('value' => 'yearly', 'text' => $this->language->get('text_period_yearly')),
            array('value' => 'custom', 'text' => $this->language->get('text_period_custom'))
        );

        // إحصائيات التخطيط
        $data['planning_statistics'] = $this->model_purchase_planning->getPlanningStatistics();

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('purchase/planning_list', $data));
    }

    /**
     * عرض نموذج إضافة/تعديل خطة شراء
     */
    protected function getForm() {
        $data['text_form'] = !isset($this->request->get['plan_id']) ? $this->language->get('text_add') : $this->language->get('text_edit');

        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }

        if (isset($this->error['plan_name'])) {
            $data['error_plan_name'] = $this->error['plan_name'];
        } else {
            $data['error_plan_name'] = '';
        }

        if (isset($this->error['start_date'])) {
            $data['error_start_date'] = $this->error['start_date'];
        } else {
            $data['error_start_date'] = '';
        }

        if (isset($this->error['end_date'])) {
            $data['error_end_date'] = $this->error['end_date'];
        } else {
            $data['error_end_date'] = '';
        }

        if (isset($this->error['total_budget'])) {
            $data['error_total_budget'] = $this->error['total_budget'];
        } else {
            $data['error_total_budget'] = '';
        }

        $url = '';

        if (isset($this->request->get['filter_name'])) {
            $url .= '&filter_name=' . urlencode(html_entity_decode($this->request->get['filter_name'], ENT_QUOTES, 'UTF-8'));
        }

        if (isset($this->request->get['filter_status'])) {
            $url .= '&filter_status=' . $this->request->get['filter_status'];
        }

        if (isset($this->request->get['filter_period'])) {
            $url .= '&filter_period=' . $this->request->get['filter_period'];
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
            'href' => $this->url->link('purchase/planning', 'user_token=' . $this->session->data['user_token'] . $url, true)
        );

        if (!isset($this->request->get['plan_id'])) {
            $data['action'] = $this->url->link('purchase/planning/add', 'user_token=' . $this->session->data['user_token'] . $url, true);
        } else {
            $data['action'] = $this->url->link('purchase/planning/edit', 'user_token=' . $this->session->data['user_token'] . '&plan_id=' . $this->request->get['plan_id'] . $url, true);
        }

        $data['cancel'] = $this->url->link('purchase/planning', 'user_token=' . $this->session->data['user_token'] . $url, true);

        if (isset($this->request->get['plan_id']) && ($this->request->server['REQUEST_METHOD'] != 'POST')) {
            $plan_info = $this->model_purchase_planning->getPlan($this->request->get['plan_id']);
        }

        $data['user_token'] = $this->session->data['user_token'];

        if (isset($this->request->post['plan_name'])) {
            $data['plan_name'] = $this->request->post['plan_name'];
        } elseif (!empty($plan_info)) {
            $data['plan_name'] = $plan_info['plan_name'];
        } else {
            $data['plan_name'] = '';
        }

        if (isset($this->request->post['plan_description'])) {
            $data['plan_description'] = $this->request->post['plan_description'];
        } elseif (!empty($plan_info)) {
            $data['plan_description'] = $plan_info['plan_description'];
        } else {
            $data['plan_description'] = '';
        }

        if (isset($this->request->post['plan_period'])) {
            $data['plan_period'] = $this->request->post['plan_period'];
        } elseif (!empty($plan_info)) {
            $data['plan_period'] = $plan_info['plan_period'];
        } else {
            $data['plan_period'] = 'monthly';
        }

        if (isset($this->request->post['start_date'])) {
            $data['start_date'] = $this->request->post['start_date'];
        } elseif (!empty($plan_info)) {
            $data['start_date'] = $plan_info['start_date'];
        } else {
            $data['start_date'] = date('Y-m-d');
        }

        if (isset($this->request->post['end_date'])) {
            $data['end_date'] = $this->request->post['end_date'];
        } elseif (!empty($plan_info)) {
            $data['end_date'] = $plan_info['end_date'];
        } else {
            $data['end_date'] = date('Y-m-d', strtotime('+1 month'));
        }

        if (isset($this->request->post['total_budget'])) {
            $data['total_budget'] = $this->request->post['total_budget'];
        } elseif (!empty($plan_info)) {
            $data['total_budget'] = $plan_info['total_budget'];
        } else {
            $data['total_budget'] = '';
        }

        if (isset($this->request->post['status'])) {
            $data['status'] = $this->request->post['status'];
        } elseif (!empty($plan_info)) {
            $data['status'] = $plan_info['status'];
        } else {
            $data['status'] = 'draft';
        }

        if (isset($this->request->post['notes'])) {
            $data['notes'] = $this->request->post['notes'];
        } elseif (!empty($plan_info)) {
            $data['notes'] = $plan_info['notes'];
        } else {
            $data['notes'] = '';
        }

        // عناصر الخطة
        if (isset($this->request->post['plan_items'])) {
            $data['plan_items'] = $this->request->post['plan_items'];
        } elseif (!empty($plan_info)) {
            $data['plan_items'] = $this->model_purchase_planning->getPlanItems($this->request->get['plan_id']);
        } else {
            $data['plan_items'] = array();
        }

        // فترات الخطة
        $data['periods'] = array(
            array('value' => 'monthly', 'text' => $this->language->get('text_period_monthly')),
            array('value' => 'quarterly', 'text' => $this->language->get('text_period_quarterly')),
            array('value' => 'yearly', 'text' => $this->language->get('text_period_yearly')),
            array('value' => 'custom', 'text' => $this->language->get('text_period_custom'))
        );

        // حالات الخطة
        $data['statuses'] = array(
            array('value' => 'draft', 'text' => $this->language->get('text_status_draft')),
            array('value' => 'active', 'text' => $this->language->get('text_status_active')),
            array('value' => 'completed', 'text' => $this->language->get('text_status_completed')),
            array('value' => 'cancelled', 'text' => $this->language->get('text_status_cancelled'))
        );

        // قائمة المنتجات
        $this->load->model('catalog/product');
        $data['products'] = $this->model_catalog_product->getProducts();

        // قائمة الفئات
        $this->load->model('catalog/category');
        $data['categories'] = $this->model_catalog_category->getCategories();

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('purchase/planning_form', $data));
    }

    /**
     * التحقق من صحة بيانات النموذج
     */
    protected function validateForm() {
        if (!$this->user->hasPermission('modify', 'purchase/planning')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        if ((utf8_strlen($this->request->post['plan_name']) < 3) || (utf8_strlen($this->request->post['plan_name']) > 255)) {
            $this->error['plan_name'] = $this->language->get('error_plan_name');
        }

        if (empty($this->request->post['start_date'])) {
            $this->error['start_date'] = $this->language->get('error_start_date');
        }

        if (empty($this->request->post['end_date'])) {
            $this->error['end_date'] = $this->language->get('error_end_date');
        }

        if (!empty($this->request->post['start_date']) && !empty($this->request->post['end_date'])) {
            if (strtotime($this->request->post['end_date']) <= strtotime($this->request->post['start_date'])) {
                $this->error['end_date'] = $this->language->get('error_end_date_before_start');
            }
        }

        if (empty($this->request->post['total_budget']) || $this->request->post['total_budget'] <= 0) {
            $this->error['total_budget'] = $this->language->get('error_total_budget');
        }

        return !$this->error;
    }

    /**
     * التحقق من صحة عملية الحذف
     */
    protected function validateDelete() {
        if (!$this->user->hasPermission('modify', 'purchase/planning')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        return !$this->error;
    }

    /**
     * الحصول على فئة CSS للحالة
     */
    private function getStatusClass($status) {
        switch ($status) {
            case 'draft':
                return 'default';
            case 'active':
                return 'info';
            case 'completed':
                return 'success';
            case 'cancelled':
                return 'danger';
            default:
                return 'default';
        }
    }

    /**
     * عرض تفاصيل خطة الشراء
     */
    public function view() {
        $this->load->language('purchase/planning');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('purchase/planning');

        if (isset($this->request->get['plan_id'])) {
            $plan_id = $this->request->get['plan_id'];
        } else {
            $plan_id = 0;
        }

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('purchase/planning', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_view_plan'),
            'href' => $this->url->link('purchase/planning/view', 'user_token=' . $this->session->data['user_token'] . '&plan_id=' . $plan_id, true)
        );

        if ($plan_id) {
            $data['plan'] = $this->model_purchase_planning->getPlan($plan_id);
            $data['plan_items'] = $this->model_purchase_planning->getPlanItems($plan_id);
            $data['plan_progress'] = $this->model_purchase_planning->getPlanProgress($plan_id);
            $data['plan_analytics'] = $this->model_purchase_planning->getPlanAnalytics($plan_id);
        } else {
            $data['plan'] = array();
            $data['plan_items'] = array();
            $data['plan_progress'] = array();
            $data['plan_analytics'] = array();
        }

        $data['user_token'] = $this->session->data['user_token'];
        $data['plan_id'] = $plan_id;

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('purchase/planning_view', $data));
    }

    /**
     * تقرير تخطيط المشتريات
     */
    public function report() {
        $this->load->language('purchase/planning');

        $this->document->setTitle($this->language->get('text_planning_report'));

        $this->load->model('purchase/planning');

        if (isset($this->request->get['date_start'])) {
            $date_start = $this->request->get['date_start'];
        } else {
            $date_start = date('Y-m-01'); // بداية الشهر الحالي
        }

        if (isset($this->request->get['date_end'])) {
            $date_end = $this->request->get['date_end'];
        } else {
            $date_end = date('Y-m-d'); // اليوم
        }

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('purchase/planning', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_planning_report'),
            'href' => $this->url->link('purchase/planning/report', 'user_token=' . $this->session->data['user_token'], true)
        );

        $filter_data = array(
            'filter_date_start' => $date_start,
            'filter_date_end' => $date_end
        );

        $data['planning_report'] = $this->model_purchase_planning->getPlanningReport($filter_data);
        $data['budget_analysis'] = $this->model_purchase_planning->getBudgetAnalysis($filter_data);
        $data['performance_metrics'] = $this->model_purchase_planning->getPerformanceMetrics($filter_data);

        $data['date_start'] = $date_start;
        $data['date_end'] = $date_end;
        $data['user_token'] = $this->session->data['user_token'];

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('purchase/planning_report', $data));
    }
}
