<?php
class ControllerHrLeave extends Controller {
    private $error = array();

    public function index() {
        $this->load->language('hr/leave');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('hr/leave');

        $data['user_token'] = $this->session->data['user_token'];

        // روابط Ajax
        $data['ajax_list_url']    = $this->url->link('hr/leave/list', 'user_token=' . $this->session->data['user_token'], true);
        $data['ajax_save_url']    = $this->url->link('hr/leave/save', 'user_token=' . $this->session->data['user_token'], true);
        $data['ajax_get_url']     = $this->url->link('hr/leave/getForm', 'user_token=' . $this->session->data['user_token'], true);
        $data['ajax_delete_url']  = $this->url->link('hr/leave/delete', 'user_token=' . $this->session->data['user_token'], true);

        // جلب الموظفين وأنواع الإجازات
        $this->load->model('user/user');
        $data['users'] = $this->model_user_user->getUsers();

        $this->load->model('hr/leave');
        $data['leave_types'] = $this->model_hr_leave->getLeaveTypes();

        // العناوين والنصوص
        $data['heading_title']        = $this->language->get('heading_title');
        $data['text_filter']          = $this->language->get('text_filter');
        $data['text_select_employee'] = $this->language->get('text_select_employee');
        $data['text_all_leave_types'] = $this->language->get('text_all_leave_types');
        $data['text_select_leave_type']= $this->language->get('text_select_leave_type');
        $data['text_all_statuses']    = $this->language->get('text_all_statuses');
        $data['text_status_pending']  = $this->language->get('text_status_pending');
        $data['text_status_approved'] = $this->language->get('text_status_approved');
        $data['text_status_rejected'] = $this->language->get('text_status_rejected');
        $data['text_status_cancelled']= $this->language->get('text_status_cancelled');
        $data['text_leave_list']      = $this->language->get('text_leave_list');
        $data['text_add_leave_request']= $this->language->get('text_add_leave_request');
        $data['text_edit_leave_request']= $this->language->get('text_edit_leave_request');
        $data['text_ajax_error']      = $this->language->get('text_ajax_error');
        $data['text_confirm_delete']  = $this->language->get('text_confirm_delete');
        $data['text_reason']          = $this->language->get('text_reason');
        $data['text_approved_by']     = $this->language->get('text_approved_by');
        $data['text_select_approver'] = $this->language->get('text_select_approver');

        $data['text_date_start']      = $this->language->get('text_date_start');
        $data['text_date_end']        = $this->language->get('text_date_end');
        $data['text_leave_type']      = $this->language->get('text_leave_type');
        $data['text_employee']        = $this->language->get('text_employee');
        $data['text_status']          = $this->language->get('text_status');

        $data['button_filter']        = $this->language->get('button_filter');
        $data['button_reset']         = $this->language->get('button_reset');
        $data['button_add_leave_request']= $this->language->get('button_add_leave_request');
        $data['button_close']         = $this->language->get('button_close');
        $data['button_save']          = $this->language->get('button_save');

        $data['column_employee']      = $this->language->get('column_employee');
        $data['column_leave_type']    = $this->language->get('column_leave_type');
        $data['column_start_date']    = $this->language->get('column_start_date');
        $data['column_end_date']      = $this->language->get('column_end_date');
        $data['column_status']        = $this->language->get('column_status');
        $data['column_actions']       = $this->language->get('column_actions');

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        // مسارات breadcrumbs
        $data['breadcrumbs'] = array();
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('hr/leave', 'user_token=' . $this->session->data['user_token'], true)
        );

        $this->response->setOutput($this->load->view('hr/leave_list', $data));
    }

    public function list() {
        $this->load->language('hr/leave');
        $this->load->model('hr/leave');

        $filter_user = isset($this->request->post['filter_user']) ? $this->request->post['filter_user'] : '';
        $filter_leave_type = isset($this->request->post['filter_leave_type']) ? $this->request->post['filter_leave_type'] : '';
        $filter_date_start = isset($this->request->post['filter_date_start']) ? $this->request->post['filter_date_start'] : '';
        $filter_date_end = isset($this->request->post['filter_date_end']) ? $this->request->post['filter_date_end'] : '';
        $filter_status = isset($this->request->post['filter_status']) ? $this->request->post['filter_status'] : '';

        $start  = isset($this->request->post['start']) ? (int)$this->request->post['start'] : 0;
        $length = isset($this->request->post['length']) ? (int)$this->request->post['length'] : 10;
        $draw   = isset($this->request->post['draw']) ? (int)$this->request->post['draw'] : 1;
        $order_column = isset($this->request->post['order'][0]['column']) ? (int)$this->request->post['order'][0]['column'] : 0;
        $order_dir = isset($this->request->post['order'][0]['dir']) ? $this->request->post['order'][0]['dir'] : 'asc';

        // أعمدة DataTable
        $columns = array('employee_name','leave_type','start_date','end_date','status');
        $order_by = isset($columns[$order_column]) ? $columns[$order_column] : 'start_date';

        $filter_data = array(
            'filter_user'       => $filter_user,
            'filter_leave_type' => $filter_leave_type,
            'filter_date_start' => $filter_date_start,
            'filter_date_end'   => $filter_date_end,
            'filter_status'     => $filter_status,
            'start'             => $start,
            'limit'             => $length,
            'order'             => $order_dir,
            'sort'              => $order_by
        );

        $total = $this->model_hr_leave->getTotalLeaveRequests($filter_data);
        $results = $this->model_hr_leave->getLeaveRequests($filter_data);

        $data = array();
        foreach ($results as $result) {
            $actions = '';
            if ($this->user->hasPermission('modify', 'hr/leave')) {
                $actions .= '<button class="btn btn-primary btn-sm btn-edit" data-id="'. $result['leave_request_id'] .'"><i class="fa fa-pencil"></i></button> ';
                $actions .= '<button class="btn btn-danger btn-sm btn-delete" data-id="'. $result['leave_request_id'] .'"><i class="fa fa-trash"></i></button>';
            } else {
                $actions .= '<button class="btn btn-primary btn-sm" disabled><i class="fa fa-pencil"></i></button> ';
                $actions .= '<button class="btn btn-danger btn-sm" disabled><i class="fa fa-trash"></i></button>';
            }

            $data[] = array(
                'employee_name' => $result['employee_name'],
                'leave_type'    => $result['leave_type_name'],
                'start_date'    => $result['start_date'],
                'end_date'      => $result['end_date'],
                'status'        => $this->language->get('text_status_'.$result['status']),
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
        $this->load->language('hr/leave');
        $this->load->model('hr/leave');

        $json = array();

        if (isset($this->request->post['leave_request_id'])) {
            $leave_request_id = (int)$this->request->post['leave_request_id'];
            $info = $this->model_hr_leave->getLeaveRequestById($leave_request_id);

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

    public function save() {
        $this->load->language('hr/leave');
        $this->load->model('hr/leave');

        $json = array();

        if (!$this->user->hasPermission('modify', 'hr/leave')) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            $leave_request_id = isset($this->request->post['leave_request_id']) ? (int)$this->request->post['leave_request_id'] : 0;

            $data = array(
                'user_id'       => $this->request->post['user_id'],
                'leave_type_id' => $this->request->post['leave_type_id'],
                'start_date'    => $this->request->post['start_date'],
                'end_date'      => $this->request->post['end_date'],
                'status'        => $this->request->post['status'],
                'reason'        => $this->request->post['reason'],
                'approved_by'   => $this->request->post['approved_by']
            );

            if (empty($data['user_id']) || empty($data['leave_type_id']) || empty($data['start_date']) || empty($data['end_date'])) {
                $json['error'] = $this->language->get('error_required');
            } else {
                if ($leave_request_id) {
                    $this->model_hr_leave->editLeaveRequest($leave_request_id, $data);
                    $json['success'] = $this->language->get('text_success_edit');
                } else {
                    $this->model_hr_leave->addLeaveRequest($data);
                    $json['success'] = $this->language->get('text_success_add');
                }
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function delete() {
        $this->load->language('hr/leave');
        $this->load->model('hr/leave');

        $json = array();

        if (!$this->user->hasPermission('modify', 'hr/leave')) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            if (isset($this->request->post['leave_request_id'])) {
                $leave_request_id = (int)$this->request->post['leave_request_id'];
                $this->model_hr_leave->deleteLeaveRequest($leave_request_id);
                $json['success'] = $this->language->get('text_success_delete');
            } else {
                $json['error'] = $this->language->get('error_invalid_request');
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
}
