<?php
class ControllerHrPerformance extends Controller {
    private $error = array();

    public function index() {
        $this->load->language('hr/performance');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('hr/performance');

        $data['user_token'] = $this->session->data['user_token'];

        // روابط Ajax
        $data['ajax_list_url']     = $this->url->link('hr/performance/list', 'user_token=' . $this->session->data['user_token'], true);
        $data['ajax_save_url']     = $this->url->link('hr/performance/save', 'user_token=' . $this->session->data['user_token'], true);
        $data['ajax_get_url']      = $this->url->link('hr/performance/getForm', 'user_token=' . $this->session->data['user_token'], true);
        $data['ajax_delete_url']   = $this->url->link('hr/performance/delete', 'user_token=' . $this->session->data['user_token'], true);
        $data['ajax_criteria_url'] = $this->url->link('hr/performance/criteriaList', 'user_token=' . $this->session->data['user_token'], true);

        // جلب الموظفين (للفلاتر وفي النموذج) والمقيّمين
        $this->load->model('user/user');
        $data['users'] = $this->model_user_user->getUsers();

        // النصوص
        $data['heading_title']          = $this->language->get('heading_title');
        $data['text_filter']            = $this->language->get('text_filter');
        $data['text_select_employee']   = $this->language->get('text_select_employee');
        $data['text_select_reviewer']   = $this->language->get('text_select_reviewer');
        $data['text_all_statuses']      = $this->language->get('text_all_statuses');
        $data['text_status_pending']    = $this->language->get('text_status_pending');
        $data['text_status_completed']  = $this->language->get('text_status_completed');
        $data['text_performance_list']  = $this->language->get('text_performance_list');
        $data['text_add_review']        = $this->language->get('text_add_review');
        $data['text_edit_review']       = $this->language->get('text_edit_review');
        $data['text_ajax_error']        = $this->language->get('text_ajax_error');
        $data['text_confirm_delete']    = $this->language->get('text_confirm_delete');
        $data['text_review_date']       = $this->language->get('text_review_date');
        $data['text_overall_score']     = $this->language->get('text_overall_score');
        $data['text_comments']          = $this->language->get('text_comments');
        $data['text_criteria_scores']   = $this->language->get('text_criteria_scores');

        $data['button_filter']          = $this->language->get('button_filter');
        $data['button_reset']           = $this->language->get('button_reset');
        $data['button_add_review']      = $this->language->get('button_add_review');
        $data['button_close']           = $this->language->get('button_close');
        $data['button_save']            = $this->language->get('button_save');

        $data['text_review_date_start'] = $this->language->get('text_review_date_start');
        $data['text_review_date_end']   = $this->language->get('text_review_date_end');
        $data['text_employee']          = $this->language->get('text_employee');
        $data['text_reviewer']          = $this->language->get('text_reviewer');
        $data['text_status']            = $this->language->get('text_status');

        $data['column_employee']        = $this->language->get('column_employee');
        $data['column_review_date']     = $this->language->get('column_review_date');
        $data['column_reviewer']        = $this->language->get('column_reviewer');
        $data['column_overall_score']   = $this->language->get('column_overall_score');
        $data['column_status']          = $this->language->get('column_status');
        $data['column_actions']         = $this->language->get('column_actions');

        $data['column_criteria_name']   = $this->language->get('column_criteria_name');
        $data['column_score']           = $this->language->get('column_score');
        $data['column_comments']        = $this->language->get('column_comments');

        // مسارات الخبز
        $data['breadcrumbs'] = array();
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('hr/performance', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('hr/performance_list', $data));
    }

    public function list() {
        $this->load->language('hr/performance');
        $this->load->model('hr/performance');

        $filter_user = isset($this->request->post['filter_user']) ? $this->request->post['filter_user'] : '';
        $filter_date_start = isset($this->request->post['filter_date_start']) ? $this->request->post['filter_date_start'] : '';
        $filter_date_end = isset($this->request->post['filter_date_end']) ? $this->request->post['filter_date_end'] : '';
        $filter_status = isset($this->request->post['filter_status']) ? $this->request->post['filter_status'] : '';

        $start  = isset($this->request->post['start']) ? (int)$this->request->post['start'] : 0;
        $length = isset($this->request->post['length']) ? (int)$this->request->post['length'] : 10;
        $draw   = isset($this->request->post['draw']) ? (int)$this->request->post['draw'] : 1;
        $order_column = isset($this->request->post['order'][0]['column']) ? (int)$this->request->post['order'][0]['column'] : 0;
        $order_dir = isset($this->request->post['order'][0]['dir']) ? $this->request->post['order'][0]['dir'] : 'asc';

        // أعمدة DataTable
        $columns = array('employee_name','review_date','reviewer_name','overall_score','status');
        $order_by = isset($columns[$order_column]) ? $columns[$order_column] : 'review_date';

        $filter_data = array(
            'filter_user'       => $filter_user,
            'filter_date_start' => $filter_date_start,
            'filter_date_end'   => $filter_date_end,
            'filter_status'     => $filter_status,
            'start'             => $start,
            'limit'             => $length,
            'order'             => $order_dir,
            'sort'              => $order_by
        );

        $total = $this->model_hr_performance->getTotalReviews($filter_data);
        $results = $this->model_hr_performance->getReviews($filter_data);

        $data = array();
        foreach ($results as $result) {
            $actions = '';
            if ($this->user->hasPermission('modify', 'hr/performance')) {
                $actions .= '<button class="btn btn-primary btn-sm btn-edit" data-id="'. $result['review_id'] .'"><i class="fa fa-pencil"></i></button> ';
                $actions .= '<button class="btn btn-danger btn-sm btn-delete" data-id="'. $result['review_id'] .'"><i class="fa fa-trash"></i></button>';
            } else {
                $actions .= '<button class="btn btn-primary btn-sm" disabled><i class="fa fa-pencil"></i></button> ';
                $actions .= '<button class="btn btn-danger btn-sm" disabled><i class="fa fa-trash"></i></button>';
            }

            $data[] = array(
                'employee_name' => $result['employee_name'],
                'review_date'   => $result['review_date'],
                'reviewer_name' => $result['reviewer_name'],
                'overall_score' => $result['overall_score'],
                'status'        => ($result['status'] == 'pending') ? $this->language->get('text_status_pending') : $this->language->get('text_status_completed'),
                'actions'       => $actions
            );
        }

        $json = array(
            "draw" => $draw,
            "recordsTotal" => $total,
            "recordsFiltered" => $total,
            "data" => $data
        );

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function getForm() {
        $this->load->language('hr/performance');
        $this->load->model('hr/performance');

        $json = array();

        if (isset($this->request->post['review_id'])) {
            $review_id = (int)$this->request->post['review_id'];
            $info = $this->model_hr_performance->getReviewById($review_id);

            if ($info) {
                $json['data'] = $info;
            } else {
                $json['error'] = $this->language->get('error_not_found');
            }
        } else {
            $json['error'] = $this->language->get('error_invalid_request');
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function criteriaList() {
        $this->load->language('hr/performance');
        $this->load->model('hr/performance');

        $review_id = isset($this->request->post['review_id']) ? (int)$this->request->post['review_id'] : 0;
        $criteria = $this->model_hr_performance->getReviewCriteria($review_id);

        $json = array();
        $json['criteria'] = array();
        foreach ($criteria as $c) {
            $json['criteria'][] = array(
                'criteria_id' => $c['criteria_id'],
                'name'        => $c['name'],
                'score'       => isset($c['score']) ? $c['score'] : 0.00,
                'comments'    => isset($c['comments']) ? $c['comments'] : ''
            );
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function save() {
        $this->load->language('hr/performance');
        $this->load->model('hr/performance');

        $json = array();

        if (!$this->user->hasPermission('modify', 'hr/performance')) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            $review_id = isset($this->request->post['review_id']) ? (int)$this->request->post['review_id'] : 0;

            $data = array(
                'user_id'       => $this->request->post['user_id'],
                'review_date'   => $this->request->post['review_date'],
                'reviewer_id'   => $this->request->post['reviewer_id'],
                'overall_score' => $this->request->post['overall_score'],
                'status'        => $this->request->post['status'],
                'comments'      => $this->request->post['comments'],
                'criteria_score'   => isset($this->request->post['criteria_score']) ? $this->request->post['criteria_score'] : array(),
                'criteria_comments'=> isset($this->request->post['criteria_comments']) ? $this->request->post['criteria_comments'] : array()
            );

            if (empty($data['user_id']) || empty($data['review_date']) || empty($data['reviewer_id'])) {
                $json['error'] = $this->language->get('error_required');
            } else {
                if ($review_id) {
                    $this->model_hr_performance->editReview($review_id, $data);
                    $json['success'] = $this->language->get('text_success_edit');
                } else {
                    $this->model_hr_performance->addReview($data);
                    $json['success'] = $this->language->get('text_success_add');
                }
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function delete() {
        $this->load->language('hr/performance');
        $this->load->model('hr/performance');

        $json = array();

        if (!$this->user->hasPermission('modify', 'hr/performance')) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            if (isset($this->request->post['review_id'])) {
                $review_id = (int)$this->request->post['review_id'];
                $this->model_hr_performance->deleteReview($review_id);
                $json['success'] = $this->language->get('text_success_delete');
            } else {
                $json['error'] = $this->language->get('error_invalid_request');
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
}
