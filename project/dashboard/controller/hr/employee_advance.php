<?php
/**
 * تحكم إدارة السلف والقروض للموظفين مع التكامل المحاسبي
 * 
 * يوفر واجهة شاملة لإدارة السلف والقروض مع:
 * - طلبات السلف والموافقة عليها
 * - جدولة الاستقطاعات
 * - الصرف والتكامل مع المحاسبة
 * - تقارير السلف المتقدمة
 * 
 * @author ERP Team
 * @version 1.0
 * @since 2024
 */

class ControllerHrEmployeeAdvance extends Controller {
    
    private $error = [];
    
    /**
     * الصفحة الرئيسية لإدارة السلف
     */
    public function index() {
        $this->load->language('hr/employee_advance');
        
        $this->document->setTitle($this->language->get('heading_title'));
        
        $this->load->model('hr/employee_advance');
        
        $this->getList();
    }
    
    /**
     * إضافة طلب سلفة جديد
     */
    public function add() {
        $this->load->language('hr/employee_advance');
        
        $this->document->setTitle($this->language->get('text_add_advance'));
        
        $this->load->model('hr/employee_advance');
        $this->load->model('hr/employee');
        
        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
            try {
                $advance_id = $this->model_hr_employee_advance->addAdvanceRequest($this->request->post);
                
                $this->session->data['success'] = $this->language->get('text_advance_added');
                
                $this->response->redirect($this->url->link('hr/employee_advance/view', 'user_token=' . $this->session->data['user_token'] . '&advance_id=' . $advance_id, true));
            } catch (Exception $e) {
                $this->error['warning'] = $e->getMessage();
            }
        }
        
        $this->getForm();
    }
    
    /**
     * تعديل طلب سلفة
     */
    public function edit() {
        $this->load->language('hr/employee_advance');
        
        $this->document->setTitle($this->language->get('text_edit_advance'));
        
        $this->load->model('hr/employee_advance');
        $this->load->model('hr/employee');
        
        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
            try {
                $this->model_hr_employee_advance->editAdvanceRequest($this->request->get['advance_id'], $this->request->post);
                
                $this->session->data['success'] = $this->language->get('text_advance_updated');
                
                $this->response->redirect($this->url->link('hr/employee_advance', 'user_token=' . $this->session->data['user_token'], true));
            } catch (Exception $e) {
                $this->error['warning'] = $e->getMessage();
            }
        }
        
        $this->getForm();
    }
    
    /**
     * عرض تفاصيل السلفة
     */
    public function view() {
        $this->load->language('hr/employee_advance');
        
        $this->document->setTitle($this->language->get('text_view_advance'));
        
        $this->load->model('hr/employee_advance');
        
        if (isset($this->request->get['advance_id'])) {
            $advance_id = (int)$this->request->get['advance_id'];
            
            $advance = $this->model_hr_employee_advance->getAdvance($advance_id);
            
            if ($advance) {
                $this->getViewForm($advance_id);
            } else {
                $this->session->data['error'] = $this->language->get('error_advance_not_found');
                $this->response->redirect($this->url->link('hr/employee_advance', 'user_token=' . $this->session->data['user_token'], true));
            }
        } else {
            $this->response->redirect($this->url->link('hr/employee_advance', 'user_token=' . $this->session->data['user_token'], true));
        }
    }
    
    /**
     * الموافقة على السلفة
     */
    public function approve() {
        $this->load->language('hr/employee_advance');
        
        $this->load->model('hr/employee_advance');
        
        $json = [];
        
        if (isset($this->request->post['advance_id'])) {
            $advance_id = (int)$this->request->post['advance_id'];
            $approval_notes = isset($this->request->post['approval_notes']) ? $this->request->post['approval_notes'] : '';
            
            try {
                $this->model_hr_employee_advance->approveAdvance($advance_id, $approval_notes);
                
                $json['success'] = $this->language->get('text_advance_approved');
            } catch (Exception $e) {
                $json['error'] = $e->getMessage();
            }
        } else {
            $json['error'] = $this->language->get('error_advance_id_required');
        }
        
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
    
    /**
     * رفض السلفة
     */
    public function reject() {
        $this->load->language('hr/employee_advance');
        
        $this->load->model('hr/employee_advance');
        
        $json = [];
        
        if (isset($this->request->post['advance_id']) && isset($this->request->post['rejection_reason'])) {
            $advance_id = (int)$this->request->post['advance_id'];
            $rejection_reason = $this->request->post['rejection_reason'];
            
            try {
                $this->model_hr_employee_advance->rejectAdvance($advance_id, $rejection_reason);
                
                $json['success'] = $this->language->get('text_advance_rejected');
            } catch (Exception $e) {
                $json['error'] = $e->getMessage();
            }
        } else {
            $json['error'] = $this->language->get('error_required_fields');
        }
        
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
    
    /**
     * صرف السلفة
     */
    public function disburse() {
        $this->load->language('hr/employee_advance');
        
        $this->load->model('hr/employee_advance');
        
        $json = [];
        
        if (isset($this->request->post['advance_id']) && isset($this->request->post['payment_method'])) {
            $advance_id = (int)$this->request->post['advance_id'];
            $payment_method = $this->request->post['payment_method'];
            
            try {
                $journal_id = $this->model_hr_employee_advance->disburseAdvance($advance_id, $payment_method);
                
                $json['success'] = $this->language->get('text_advance_disbursed');
                $json['journal_id'] = $journal_id;
            } catch (Exception $e) {
                $json['error'] = $e->getMessage();
            }
        } else {
            $json['error'] = $this->language->get('error_required_fields');
        }
        
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
    
    /**
     * لوحة تحكم السلف
     */
    public function dashboard() {
        $this->load->language('hr/employee_advance');
        
        $this->document->setTitle($this->language->get('text_advance_dashboard'));
        
        $this->load->model('hr/employee_advance');
        
        $data['breadcrumbs'] = [];
        
        $data['breadcrumbs'][] = [
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        ];
        
        $data['breadcrumbs'][] = [
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('hr/employee_advance', 'user_token=' . $this->session->data['user_token'], true)
        ];
        
        $data['breadcrumbs'][] = [
            'text' => $this->language->get('text_advance_dashboard'),
            'href' => $this->url->link('hr/employee_advance/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        ];
        
        // إحصائيات السلف
        $data['statistics'] = $this->getAdvanceStatistics();
        
        // الرسوم البيانية
        $data['charts_data'] = $this->getChartsData();
        
        $data['user_token'] = $this->session->data['user_token'];
        
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');
        
        $this->response->setOutput($this->load->view('hr/employee_advance_dashboard', $data));
    }
    
    /**
     * تقرير الأقساط المستحقة
     */
    public function pendingInstallments() {
        $this->load->language('hr/employee_advance');
        
        $this->document->setTitle($this->language->get('text_pending_installments'));
        
        $this->load->model('hr/employee_advance');
        $this->load->model('hr/employee');
        
        $data['breadcrumbs'] = [];
        
        $data['breadcrumbs'][] = [
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        ];
        
        $data['breadcrumbs'][] = [
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('hr/employee_advance', 'user_token=' . $this->session->data['user_token'], true)
        ];
        
        $data['breadcrumbs'][] = [
            'text' => $this->language->get('text_pending_installments'),
            'href' => $this->url->link('hr/employee_advance/pendingInstallments', 'user_token=' . $this->session->data['user_token'], true)
        ];
        
        // الحصول على الموظفين النشطين
        $employees = $this->model_hr_employee->getActiveEmployees();
        
        $data['employees'] = [];
        
        foreach ($employees as $employee) {
            $pending_installments = $this->model_hr_employee_advance->getEmployeePendingInstallments($employee['employee_id']);
            
            if (!empty($pending_installments)) {
                $total_pending = 0;
                foreach ($pending_installments as $installment) {
                    $total_pending += $installment['installment_amount'];
                }
                
                $data['employees'][] = [
                    'employee_id' => $employee['employee_id'],
                    'employee_name' => $employee['firstname'] . ' ' . $employee['lastname'],
                    'job_title' => $employee['job_title'],
                    'pending_installments' => $pending_installments,
                    'total_pending' => $this->currency->format($total_pending, $this->config->get('config_currency')),
                    'installment_count' => count($pending_installments)
                ];
            }
        }
        
        $data['user_token'] = $this->session->data['user_token'];
        
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');
        
        $this->response->setOutput($this->load->view('hr/employee_advance_pending', $data));
    }
    
    /**
     * عرض قائمة السلف
     */
    protected function getList() {
        if (isset($this->request->get['filter_employee'])) {
            $filter_employee = $this->request->get['filter_employee'];
        } else {
            $filter_employee = '';
        }
        
        if (isset($this->request->get['filter_status'])) {
            $filter_status = $this->request->get['filter_status'];
        } else {
            $filter_status = '';
        }
        
        if (isset($this->request->get['filter_type'])) {
            $filter_type = $this->request->get['filter_type'];
        } else {
            $filter_type = '';
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
        
        if (isset($this->request->get['page'])) {
            $page = (int)$this->request->get['page'];
        } else {
            $page = 1;
        }
        
        $data['breadcrumbs'] = [];
        
        $data['breadcrumbs'][] = [
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        ];
        
        $data['breadcrumbs'][] = [
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('hr/employee_advance', 'user_token=' . $this->session->data['user_token'], true)
        ];
        
        $data['add'] = $this->url->link('hr/employee_advance/add', 'user_token=' . $this->session->data['user_token'], true);
        $data['dashboard'] = $this->url->link('hr/employee_advance/dashboard', 'user_token=' . $this->session->data['user_token'], true);
        $data['pending_installments'] = $this->url->link('hr/employee_advance/pendingInstallments', 'user_token=' . $this->session->data['user_token'], true);
        
        $data['advances'] = [];
        
        $filter_data = [
            'filter_employee' => $filter_employee,
            'filter_status' => $filter_status,
            'filter_type' => $filter_type,
            'filter_date_start' => $filter_date_start,
            'filter_date_end' => $filter_date_end,
            'start' => ($page - 1) * $this->config->get('config_limit_admin'),
            'limit' => $this->config->get('config_limit_admin')
        ];
        
        $advances = $this->model_hr_employee_advance->getAdvances($filter_data);
        
        foreach ($advances as $advance) {
            $progress = 0;
            if ($advance['total_installments'] > 0) {
                $progress = ($advance['paid_installments'] / $advance['total_installments']) * 100;
            }
            
            $data['advances'][] = [
                'advance_id' => $advance['advance_id'],
                'employee_name' => $advance['employee_name'],
                'job_title' => $advance['job_title'],
                'advance_type' => $advance['advance_type'],
                'amount' => $this->currency->format($advance['amount'], $this->config->get('config_currency')),
                'installments' => $advance['installments'],
                'progress' => round($progress, 1),
                'paid_installments' => $advance['paid_installments'],
                'total_installments' => $advance['total_installments'],
                'status' => $advance['status'],
                'status_text' => $this->language->get('text_status_' . $advance['status']),
                'requested_by_name' => $advance['requested_by_name'],
                'date_requested' => date($this->language->get('datetime_format'), strtotime($advance['date_requested'])),
                'view' => $this->url->link('hr/employee_advance/view', 'user_token=' . $this->session->data['user_token'] . '&advance_id=' . $advance['advance_id'], true),
                'edit' => $this->url->link('hr/employee_advance/edit', 'user_token=' . $this->session->data['user_token'] . '&advance_id=' . $advance['advance_id'], true)
            ];
        }
        
        // إحصائيات سريعة
        $data['statistics'] = $this->getQuickStatistics();
        
        // قائمة الموظفين للفلترة
        $this->load->model('hr/employee');
        $data['employees'] = $this->model_hr_employee->getActiveEmployees();
        
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
        
        $data['filter_employee'] = $filter_employee;
        $data['filter_status'] = $filter_status;
        $data['filter_type'] = $filter_type;
        $data['filter_date_start'] = $filter_date_start;
        $data['filter_date_end'] = $filter_date_end;
        
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');
        
        $this->response->setOutput($this->load->view('hr/employee_advance_list', $data));
    }
