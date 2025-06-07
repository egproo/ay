<?php
class ControllerHrAttendance extends Controller {
    private $error = array();

    public function index() {
        $this->load->language('hr/attendance');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('hr/attendance');

        // إعداد روابط Ajax
        $data['ajax_list_url'] = $this->url->link('hr/attendance/list', 'user_token=' . $this->session->data['user_token'], true);
        $data['ajax_save_url'] = $this->url->link('hr/attendance/save', 'user_token=' . $this->session->data['user_token'], true);
        $data['ajax_get_url']  = $this->url->link('hr/attendance/getForm', 'user_token=' . $this->session->data['user_token'], true);
        $data['ajax_delete_url']= $this->url->link('hr/attendance/delete', 'user_token=' . $this->session->data['user_token'], true);

        // تمرير متغيرات إلى القالب
        $data['user_token'] = $this->session->data['user_token'];

        // مسارات الخبز
        $data['breadcrumbs'] = array();
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('hr/attendance', 'user_token=' . $this->session->data['user_token'], true)
        );

        // جلب قائمة الموظفين لإظهارها بالفلاتر
        $this->load->model('user/user');
        $data['users'] = $this->model_user_user->getUsers(); 

        // عناوين النصوص
        $data['heading_title']    = $this->language->get('heading_title');
        $data['text_filter']      = $this->language->get('text_filter');
        $data['text_select_employee'] = $this->language->get('text_select_employee');
        $data['text_date']        = $this->language->get('text_date');
        $data['text_add_attendance'] = $this->language->get('text_add_attendance');
        $data['text_edit_attendance'] = $this->language->get('text_edit_attendance');
        $data['text_present']     = $this->language->get('text_present');
        $data['text_absent']      = $this->language->get('text_absent');
        $data['text_late']        = $this->language->get('text_late');
        $data['text_on_leave']    = $this->language->get('text_on_leave');
        $data['text_attendance_list'] = $this->language->get('text_attendance_list');
        $data['text_ajax_error']  = $this->language->get('text_ajax_error');
        $data['text_confirm_delete'] = $this->language->get('text_confirm_delete');

        $data['button_filter']    = $this->language->get('button_filter');
        $data['button_reset']     = $this->language->get('button_reset');
        $data['button_add_attendance'] = $this->language->get('button_add_attendance');
        $data['button_close']     = $this->language->get('button_close');
        $data['button_save']      = $this->language->get('button_save');

        $data['column_employee']  = $this->language->get('column_employee');
        $data['column_date']      = $this->language->get('column_date');
        $data['column_checkin']   = $this->language->get('column_checkin');
        $data['column_checkout']  = $this->language->get('column_checkout');
        $data['column_status']    = $this->language->get('column_status');
        $data['column_actions']   = $this->language->get('column_actions');
        $data['text_notes']       = $this->language->get('text_notes');
        $data['text_employee']    = $this->language->get('text_employee');
        $data['text_status']      = $this->language->get('text_status');
        $data['text_checkin']     = $this->language->get('text_checkin');
        $data['text_checkout']    = $this->language->get('text_checkout');

        $data['text_date_start']  = $this->language->get('text_date_start');
        $data['text_date_end']    = $this->language->get('text_date_end');

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('hr/attendance_list', $data));
    }

    public function list() {
        $this->load->language('hr/attendance');
        $this->load->model('hr/attendance');

        $filter_user = isset($this->request->post['filter_user']) ? $this->request->post['filter_user'] : '';
        $filter_date_start = isset($this->request->post['filter_date_start']) ? $this->request->post['filter_date_start'] : '';
        $filter_date_end = isset($this->request->post['filter_date_end']) ? $this->request->post['filter_date_end'] : '';

        $start  = isset($this->request->post['start']) ? (int)$this->request->post['start'] : 0;
        $length = isset($this->request->post['length']) ? (int)$this->request->post['length'] : 10;
        $draw   = isset($this->request->post['draw']) ? (int)$this->request->post['draw'] : 1;
        $order_column = isset($this->request->post['order'][0]['column']) ? (int)$this->request->post['order'][0]['column'] : 0;
        $order_dir = isset($this->request->post['order'][0]['dir']) ? $this->request->post['order'][0]['dir'] : 'asc';

        // خرائط لأعمدة DataTables
        $columns = array('employee_name','date','checkin_time','checkout_time','status');
        $order_by = isset($columns[$order_column]) ? $columns[$order_column] : 'date';

        $filter_data = array(
            'filter_user'      => $filter_user,
            'filter_date_start'=> $filter_date_start,
            'filter_date_end'  => $filter_date_end,
            'start'            => $start,
            'limit'            => $length,
            'order'            => $order_dir,
            'sort'             => $order_by
        );

        $attendance_total = $this->model_hr_attendance->getTotalAttendance($filter_data);
        $results = $this->model_hr_attendance->getAttendance($filter_data);

        $data = array();
        foreach ($results as $result) {
            $actions = '';
            if ($this->user->hasPermission('modify', 'hr/attendance')) {
                $actions .= '<button class="btn btn-primary btn-sm btn-edit" data-id="'. $result['attendance_id'] .'"><i class="fa fa-pencil"></i></button> ';
                $actions .= '<button class="btn btn-danger btn-sm btn-delete" data-id="'. $result['attendance_id'] .'"><i class="fa fa-trash"></i></button>';
            } else {
                $actions .= '<button class="btn btn-primary btn-sm" disabled><i class="fa fa-pencil"></i></button> ';
                $actions .= '<button class="btn btn-danger btn-sm" disabled><i class="fa fa-trash"></i></button>';
            }

            $data[] = array(
                'employee_name' => $result['employee_name'],
                'date'          => $result['date'],
                'checkin_time'  => $result['checkin_time'],
                'checkout_time' => $result['checkout_time'],
                'status'        => $this->language->get('text_'.$result['status']),
                'actions'       => $actions
            );
        }

        $json = array(
            "draw"            => $draw,
            "recordsTotal"    => $attendance_total,
            "recordsFiltered" => $attendance_total,
            "data"            => $data
        );

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function getForm() {
        $this->load->language('hr/attendance');
        $this->load->model('hr/attendance');

        $json = array();

        if (isset($this->request->post['attendance_id'])) {
            $attendance_id = (int)$this->request->post['attendance_id'];
            $attendance_info = $this->model_hr_attendance->getAttendanceById($attendance_id);

            if ($attendance_info) {
                $json['data'] = $attendance_info;
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
        $this->load->language('hr/attendance');
        $this->load->model('hr/attendance');

        $json = array();

        if (!$this->user->hasPermission('modify', 'hr/attendance')) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            $attendance_id = isset($this->request->post['attendance_id']) ? (int)$this->request->post['attendance_id'] : 0;

            $data = array(
                'user_id'      => $this->request->post['user_id'],
                'date'         => $this->request->post['date'],
                'checkin_time' => $this->request->post['checkin_time'],
                'checkout_time'=> $this->request->post['checkout_time'],
                'status'       => $this->request->post['status'],
                'notes'        => $this->request->post['notes'],
            );

            // تحقق من البيانات إن أردت
            if (empty($data['user_id']) || empty($data['date'])) {
                $json['error'] = $this->language->get('error_required');
            } else {
                if ($attendance_id) {
                    // تعديل
                    $this->model_hr_attendance->editAttendance($attendance_id, $data);
                    $json['success'] = $this->language->get('text_success_edit');
                } else {
                    // إضافة
                    $this->model_hr_attendance->addAttendance($data);
                    $json['success'] = $this->language->get('text_success_add');
                }
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function delete() {
        $this->load->language('hr/attendance');
        $this->load->model('hr/attendance');

        $json = array();

        if (!$this->user->hasPermission('modify', 'hr/attendance')) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            if (isset($this->request->post['attendance_id'])) {
                $attendance_id = (int)$this->request->post['attendance_id'];
                $this->model_hr_attendance->deleteAttendance($attendance_id);
                $json['success'] = $this->language->get('text_success_delete');
            } else {
                $json['error'] = $this->language->get('error_invalid_request');
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
}
