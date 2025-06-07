<?php
/**
 * AYM ERP - Advanced Human Resources Management Controller
 *
 * Professional HRM system with comprehensive employee lifecycle management
 * Features:
 * - Complete employee information management
 * - Advanced attendance and time tracking
 * - Payroll integration and salary management
 * - Performance evaluation and appraisals
 * - Leave management with approval workflows
 * - Training and development tracking
 * - Document management and compliance
 * - Employee self-service portal
 *
 * @author AYM ERP Development Team
 * @copyright 2024 AYM ERP
 * @license Commercial License
 * @version 1.0.0
 * @link https://aym-erp.com
 */

class ControllerHrEmployee extends Controller {
    private $error = array();

    public function index() {
        $this->load->language('hr/employee');
        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('hr/employee');
        $this->load->model('user/user');

        $data['user_token'] = $this->session->data['user_token'];

        // روابط Ajax
        $data['ajax_list_url'] = $this->url->link('hr/employee/list', 'user_token=' . $this->session->data['user_token'], true);
        $data['ajax_save_url'] = $this->url->link('hr/employee/save', 'user_token=' . $this->session->data['user_token'], true);
        $data['ajax_get_url']  = $this->url->link('hr/employee/getForm', 'user_token=' . $this->session->data['user_token'], true);
        $data['ajax_delete_url'] = $this->url->link('hr/employee/delete', 'user_token=' . $this->session->data['user_token'], true);
        $data['ajax_documents_list_url'] = $this->url->link('hr/employee/documentsList', 'user_token=' . $this->session->data['user_token'], true);
        $data['ajax_document_upload_url'] = $this->url->link('hr/employee/documentUpload', 'user_token=' . $this->session->data['user_token'], true);
        $data['ajax_document_delete_url'] = $this->url->link('hr/employee/documentDelete', 'user_token=' . $this->session->data['user_token'], true);

        // جلب قائمة المستخدمين لاختيار الموظف
        $users = $this->model_user_user->getUsers();
        $data['users'] = array();
        foreach ($users as $u) {
            $data['users'][] = array(
                'user_id'   => $u['user_id'],
                'firstname' => $u['firstname'],
                'lastname'  => $u['lastname'],
                'email'     => $u['email']
            );
        }

        // النصوص
        $data['heading_title']          = $this->language->get('heading_title');
        $data['text_filter']            = $this->language->get('text_filter');
        $data['text_enter_employee_name']= $this->language->get('text_enter_employee_name');
        $data['text_all_statuses']      = $this->language->get('text_all_statuses');
        $data['text_active']            = $this->language->get('text_active');
        $data['text_inactive']          = $this->language->get('text_inactive');
        $data['text_terminated']        = $this->language->get('text_terminated');
        $data['button_filter']          = $this->language->get('button_filter');
        $data['button_reset']           = $this->language->get('button_reset');
        $data['button_add_employee']    = $this->language->get('button_add_employee');
        $data['text_employee_list']     = $this->language->get('text_employee_list');
        $data['text_add_employee']      = $this->language->get('text_add_employee');
        $data['text_edit_employee']     = $this->language->get('text_edit_employee');
        $data['text_ajax_error']        = $this->language->get('text_ajax_error');
        $data['text_confirm_delete']    = $this->language->get('text_confirm_delete');
        $data['text_user_id']           = $this->language->get('text_user_id');
        $data['text_select_user']       = $this->language->get('text_select_user');
        $data['text_job_title']         = $this->language->get('text_job_title');
        $data['text_hiring_date']       = $this->language->get('text_hiring_date');
        $data['text_salary']            = $this->language->get('text_salary');
        $data['text_status']            = $this->language->get('text_status');
        $data['text_documents']         = $this->language->get('text_documents');
        $data['button_add_document']    = $this->language->get('button_add_document');
        $data['column_document_name']   = $this->language->get('column_document_name');
        $data['column_document_description'] = $this->language->get('column_document_description');
        $data['column_document_actions'] = $this->language->get('column_document_actions');
        $data['text_add_document']      = $this->language->get('text_add_document');
        $data['text_document_name']     = $this->language->get('text_document_name');
        $data['text_document_description'] = $this->language->get('text_document_description');
        $data['text_file']              = $this->language->get('text_file');
        $data['text_save_employee_first'] = $this->language->get('text_save_employee_first');

        $data['button_close'] = $this->language->get('button_close');
        $data['button_save']  = $this->language->get('button_save');

        $data['column_employee_name'] = $this->language->get('column_employee_name');
        $data['column_job_title']     = $this->language->get('column_job_title');
        $data['column_status']        = $this->language->get('column_status');
        $data['column_salary']        = $this->language->get('column_salary');
        $data['column_hiring_date']   = $this->language->get('column_hiring_date');
        $data['column_actions']       = $this->language->get('column_actions');
        $data['text_employee_name']   = $this->language->get('text_employee_name');

        $data['breadcrumbs'] = array();
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard','user_token=' . $this->session->data['user_token'],true)
        );
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('hr/employee','user_token=' . $this->session->data['user_token'],true)
        );

        $data['header']     = $this->load->controller('common/header');
        $data['column_left']= $this->load->controller('common/column_left');
        $data['footer']     = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('hr/employee_list', $data));
    }

    public function list() {
        $this->load->language('hr/employee');
        $this->load->model('hr/employee');

        $filter_name = isset($this->request->post['filter_name']) ? $this->request->post['filter_name'] : '';
        $filter_status = isset($this->request->post['filter_status']) ? $this->request->post['filter_status'] : '';

        $start  = isset($this->request->post['start']) ? (int)$this->request->post['start'] : 0;
        $length = isset($this->request->post['length']) ? (int)$this->request->post['length'] : 10;
        $draw   = isset($this->request->post['draw']) ? (int)$this->request->post['draw'] : 1;
        $order_column = isset($this->request->post['order'][0]['column']) ? (int)$this->request->post['order'][0]['column'] : 0;
        $order_dir = isset($this->request->post['order'][0]['dir']) ? $this->request->post['order'][0]['dir'] : 'asc';

        $columns = array('employee_name','job_title','status','salary','hiring_date');
        $sort = isset($columns[$order_column]) ? $columns[$order_column] : 'hiring_date';

        $filter_data = array(
            'filter_name'   => $filter_name,
            'filter_status' => $filter_status,
            'start'         => $start,
            'limit'         => $length,
            'sort'          => $sort,
            'order'         => $order_dir
        );

        $total = $this->model_hr_employee->getTotalEmployees($filter_data);
        $results = $this->model_hr_employee->getEmployees($filter_data);

        $data = array();
        foreach ($results as $result) {
            $actions = '';
            if ($this->user->hasPermission('modify', 'hr/employee')) {
                $actions .= '<button class="btn btn-primary btn-sm btn-edit" data-id="'. $result['employee_id'] .'"><i class="fa fa-pencil"></i></button> ';
                $actions .= '<button class="btn btn-danger btn-sm btn-delete" data-id="'. $result['employee_id'] .'"><i class="fa fa-trash"></i></button>';
            } else {
                $actions .= '<button class="btn btn-primary btn-sm" disabled><i class="fa fa-pencil"></i></button> ';
                $actions .= '<button class="btn btn-danger btn-sm" disabled><i class="fa fa-trash"></i></button>';
            }

            $data[] = array(
                'employee_name' => $result['employee_name'],
                'job_title'     => $result['job_title'],
                'status'        => $this->language->get('text_'.$result['status']),
                'salary'        => $result['salary'],
                'hiring_date'   => $result['hiring_date'],
                'actions'       => $actions
            );
        }

        $json = array(
            "draw"            => $draw,
            "recordsTotal"    => $total,
            "recordsFiltered" => $total,
            "data"            => $data
        );

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function getForm() {
        $this->load->language('hr/employee');
        $this->load->model('hr/employee');

        $json = array();
        if (isset($this->request->post['employee_id'])) {
            $employee_id = (int)$this->request->post['employee_id'];
            $info = $this->model_hr_employee->getEmployee($employee_id);

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
        $this->load->language('hr/employee');
        $this->load->model('hr/employee');

        $json = array();

        if (!$this->user->hasPermission('modify', 'hr/employee')) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            $employee_id = isset($this->request->post['employee_id']) ? (int)$this->request->post['employee_id'] : 0;

            $data = array(
                'user_id'    => $this->request->post['user_id'],
                'job_title'  => $this->request->post['job_title'],
                'hiring_date'=> $this->request->post['hiring_date'],
                'salary'     => $this->request->post['salary'],
                'status'     => $this->request->post['status']
            );

            if (empty($data['user_id']) || empty($data['job_title']) || empty($data['hiring_date']) || empty($data['salary'])) {
                $json['error'] = $this->language->get('error_required');
            } else {
                if ($employee_id) {
                    $this->model_hr_employee->editEmployee($employee_id, $data);
                    $json['success'] = $this->language->get('text_success_edit');
                } else {
                    $employee_id = $this->model_hr_employee->addEmployee($data);
                    $json['success'] = $this->language->get('text_success_add');
                }
                // بعد الحفظ يمكننا إرجاع الـemployee_id إذا احتجنا
                $json['employee_id'] = $employee_id;
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function delete() {
        $this->load->language('hr/employee');
        $this->load->model('hr/employee');

        $json = array();
        if (!$this->user->hasPermission('modify', 'hr/employee')) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            if (isset($this->request->post['employee_id'])) {
                $employee_id = (int)$this->request->post['employee_id'];
                $this->model_hr_employee->deleteEmployee($employee_id);
                $json['success'] = $this->language->get('text_success_delete');
            } else {
                $json['error'] = $this->language->get('error_invalid_request');
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function documentsList() {
        $this->load->language('hr/employee');
        $this->load->model('hr/employee');

        $json = array();
        if (isset($this->request->post['employee_id'])) {
            $employee_id = (int)$this->request->post['employee_id'];
            $docs = $this->model_hr_employee->getEmployeeDocuments($employee_id);

            $json['data'] = array();
            foreach ($docs as $doc) {
                // نفترض أن الملفات موجودة في directory معين ونحفظ مسارها في file_path
                $file_url = HTTPS_CATALOG . 'documents/employees/' . $doc['file_path'];
                $json['data'][] = array(
                    'document_id'   => $doc['document_id'],
                    'document_name' => $doc['document_name'],
                    'description'   => $doc['description'],
                    'file_url'      => $file_url
                );
            }
        } else {
            $json['data'] = array();
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function documentUpload() {
        $this->load->language('hr/employee');
        $this->load->model('hr/employee');

        $json = array();
        if (!$this->user->hasPermission('modify', 'hr/employee')) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            if (isset($this->request->post['employee_id']) && !empty($this->request->post['employee_id'])) {
                $employee_id = (int)$this->request->post['employee_id'];
                $document_name = $this->request->post['document_name'];
                $description = $this->request->post['description'];

                if (empty($document_name) || !isset($_FILES['file'])) {
                    $json['error'] = $this->language->get('error_required');
                } else {
                    // رفع الملف
                    $file = $_FILES['file'];
                    $filename = time().'_'.basename($file['name']);
                    $target_dir = DIR_DOCUMENTS . 'employees/';
                    if (!is_dir($target_dir)) { mkdir($target_dir, 0777, true); }

                    $target_file = $target_dir . $filename;
                    if (move_uploaded_file($file['tmp_name'], $target_file)) {
                        // حفظ في قاعدة البيانات
                        $this->model_hr_employee->addEmployeeDocument($employee_id, array(
                            'document_name' => $document_name,
                            'description'   => $description,
                            'file_path'     => $filename
                        ));
                        $json['success'] = $this->language->get('text_success_document_add');
                    } else {
                        $json['error'] = $this->language->get('error_upload');
                    }
                }
            } else {
                $json['error'] = $this->language->get('error_invalid_request');
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function documentDelete() {
        $this->load->language('hr/employee');
        $this->load->model('hr/employee');

        $json = array();
        if (!$this->user->hasPermission('modify', 'hr/employee')) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            if (isset($this->request->post['document_id'])) {
                $document_id = (int)$this->request->post['document_id'];
                // جلب معلومات المستند لحذف الملف من السيرفر إن أردت
                $docInfo = $this->model_hr_employee->getEmployeeDocument($document_id);
                if ($docInfo) {
                    // احذف الملف الفيزيائي
                    $filepath = DIR_DOCUMENTS . 'employees/' . $docInfo['file_path'];
                    if (is_file($filepath)) {
                        unlink($filepath);
                    }
                    // احذف من قاعدة البيانات
                    $this->model_hr_employee->deleteEmployeeDocument($document_id);
                    $json['success'] = $this->language->get('text_success_document_delete');
                } else {
                    $json['error'] = $this->language->get('error_not_found');
                }
            } else {
                $json['error'] = $this->language->get('error_invalid_request');
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function dashboard() {
        $this->load->language('hr/employee');
        $this->load->model('hr/employee');

        $this->document->setTitle($this->language->get('text_hr_dashboard'));

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_hr_dashboard'),
            'href' => $this->url->link('hr/employee/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );

        // Get HR statistics
        $data['hr_stats'] = $this->model_hr_employee->getHRStatistics();

        // Get attendance summary
        $data['attendance_summary'] = $this->model_hr_employee->getAttendanceSummary();

        // Get leave requests
        $data['pending_leave_requests'] = $this->model_hr_employee->getPendingLeaveRequests(10);

        // Get upcoming birthdays
        $data['upcoming_birthdays'] = $this->model_hr_employee->getUpcomingBirthdays(10);

        // Get new hires
        $data['new_hires'] = $this->model_hr_employee->getNewHires(10);

        // Get performance metrics
        $data['performance_metrics'] = $this->model_hr_employee->getPerformanceMetrics();

        // Get department statistics
        $data['department_stats'] = $this->model_hr_employee->getDepartmentStatistics();

        $data['user_token'] = $this->session->data['user_token'];

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('hr/employee_dashboard', $data));
    }

    public function profile() {
        $this->load->language('hr/employee');
        $this->load->model('hr/employee');

        if (!isset($this->request->get['employee_id'])) {
            $this->response->redirect($this->url->link('hr/employee', 'user_token=' . $this->session->data['user_token'], true));
        }

        $employee_id = $this->request->get['employee_id'];
        $employee_info = $this->model_hr_employee->getEmployee($employee_id);

        if (!$employee_info) {
            $this->response->redirect($this->url->link('hr/employee', 'user_token=' . $this->session->data['user_token'], true));
        }

        $this->document->setTitle($this->language->get('text_employee_profile') . ' - ' . $employee_info['firstname'] . ' ' . $employee_info['lastname']);

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('hr/employee', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_employee_profile'),
            'href' => $this->url->link('hr/employee/profile', 'user_token=' . $this->session->data['user_token'] . '&employee_id=' . $employee_id, true)
        );

        // Get employee details
        $data['employee'] = $employee_info;

        // Get employee attendance
        $data['attendance_records'] = $this->model_hr_employee->getEmployeeAttendance($employee_id, 30);

        // Get employee leave history
        $data['leave_history'] = $this->model_hr_employee->getEmployeeLeaveHistory($employee_id, 20);

        // Get employee performance reviews
        $data['performance_reviews'] = $this->model_hr_employee->getEmployeePerformanceReviews($employee_id);

        // Get employee training records
        $data['training_records'] = $this->model_hr_employee->getEmployeeTrainingRecords($employee_id);

        // Get employee salary history
        $data['salary_history'] = $this->model_hr_employee->getEmployeeSalaryHistory($employee_id);

        $data['user_token'] = $this->session->data['user_token'];

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('hr/employee_profile', $data));
    }

    public function attendance() {
        $this->load->language('hr/employee');
        $this->load->model('hr/employee');

        $json = array('success' => false);

        if ($this->request->server['REQUEST_METHOD'] == 'POST') {
            $attendance_data = $this->request->post;

            try {
                if ($this->validateAttendance($attendance_data)) {
                    $attendance_id = $this->model_hr_employee->recordAttendance($attendance_data);

                    if ($attendance_id) {
                        $json['success'] = true;
                        $json['attendance_id'] = $attendance_id;
                        $json['message'] = $this->language->get('text_attendance_recorded');
                    } else {
                        $json['error'] = $this->language->get('error_attendance_failed');
                    }
                } else {
                    $json['error'] = $this->error;
                }
            } catch (Exception $e) {
                $json['error'] = $e->getMessage();
            }
        } else {
            $json['error'] = $this->language->get('error_invalid_request');
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function leaveRequest() {
        $this->load->language('hr/employee');
        $this->load->model('hr/employee');

        $json = array('success' => false);

        if ($this->request->server['REQUEST_METHOD'] == 'POST') {
            $leave_data = $this->request->post;

            try {
                if ($this->validateLeaveRequest($leave_data)) {
                    $leave_id = $this->model_hr_employee->submitLeaveRequest($leave_data);

                    if ($leave_id) {
                        $json['success'] = true;
                        $json['leave_id'] = $leave_id;
                        $json['message'] = $this->language->get('text_leave_request_submitted');

                        // Send notification to manager
                        $this->model_hr_employee->sendLeaveRequestNotification($leave_id);
                    } else {
                        $json['error'] = $this->language->get('error_leave_request_failed');
                    }
                } else {
                    $json['error'] = $this->error;
                }
            } catch (Exception $e) {
                $json['error'] = $e->getMessage();
            }
        } else {
            $json['error'] = $this->language->get('error_invalid_request');
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function approveLeave() {
        $this->load->language('hr/employee');
        $this->load->model('hr/employee');

        $json = array('success' => false);

        if ($this->request->server['REQUEST_METHOD'] == 'POST') {
            $leave_id = isset($this->request->post['leave_id']) ? (int)$this->request->post['leave_id'] : 0;
            $action = isset($this->request->post['action']) ? $this->request->post['action'] : '';
            $comments = isset($this->request->post['comments']) ? $this->request->post['comments'] : '';

            try {
                if ($leave_id && in_array($action, array('approve', 'reject'))) {
                    $result = $this->model_hr_employee->processLeaveRequest($leave_id, $action, $comments, $this->user->getId());

                    if ($result) {
                        $json['success'] = true;
                        $json['message'] = $this->language->get('text_leave_' . $action . '_success');

                        // Send notification to employee
                        $this->model_hr_employee->sendLeaveDecisionNotification($leave_id, $action);
                    } else {
                        $json['error'] = $this->language->get('error_leave_process_failed');
                    }
                } else {
                    $json['error'] = $this->language->get('error_invalid_data');
                }
            } catch (Exception $e) {
                $json['error'] = $e->getMessage();
            }
        } else {
            $json['error'] = $this->language->get('error_invalid_request');
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function performanceReview() {
        $this->load->language('hr/employee');
        $this->load->model('hr/employee');

        $json = array('success' => false);

        if ($this->request->server['REQUEST_METHOD'] == 'POST') {
            $review_data = $this->request->post;

            try {
                if ($this->validatePerformanceReview($review_data)) {
                    $review_id = $this->model_hr_employee->createPerformanceReview($review_data);

                    if ($review_id) {
                        $json['success'] = true;
                        $json['review_id'] = $review_id;
                        $json['message'] = $this->language->get('text_performance_review_created');
                    } else {
                        $json['error'] = $this->language->get('error_performance_review_failed');
                    }
                } else {
                    $json['error'] = $this->error;
                }
            } catch (Exception $e) {
                $json['error'] = $e->getMessage();
            }
        } else {
            $json['error'] = $this->language->get('error_invalid_request');
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    protected function validateAttendance($data) {
        if (empty($data['employee_id'])) {
            $this->error['employee'] = $this->language->get('error_employee_required');
        }

        if (empty($data['attendance_date'])) {
            $this->error['date'] = $this->language->get('error_date_required');
        }

        if (empty($data['check_in_time'])) {
            $this->error['check_in'] = $this->language->get('error_check_in_required');
        }

        return !$this->error;
    }

    protected function validateLeaveRequest($data) {
        if (empty($data['employee_id'])) {
            $this->error['employee'] = $this->language->get('error_employee_required');
        }

        if (empty($data['leave_type_id'])) {
            $this->error['leave_type'] = $this->language->get('error_leave_type_required');
        }

        if (empty($data['start_date'])) {
            $this->error['start_date'] = $this->language->get('error_start_date_required');
        }

        if (empty($data['end_date'])) {
            $this->error['end_date'] = $this->language->get('error_end_date_required');
        }

        if (!empty($data['start_date']) && !empty($data['end_date'])) {
            if (strtotime($data['start_date']) > strtotime($data['end_date'])) {
                $this->error['date_range'] = $this->language->get('error_invalid_date_range');
            }
        }

        return !$this->error;
    }

    protected function validatePerformanceReview($data) {
        if (empty($data['employee_id'])) {
            $this->error['employee'] = $this->language->get('error_employee_required');
        }

        if (empty($data['review_period_start'])) {
            $this->error['period_start'] = $this->language->get('error_period_start_required');
        }

        if (empty($data['review_period_end'])) {
            $this->error['period_end'] = $this->language->get('error_period_end_required');
        }

        if (empty($data['overall_rating'])) {
            $this->error['rating'] = $this->language->get('error_rating_required');
        }

        return !$this->error;
    }
}
