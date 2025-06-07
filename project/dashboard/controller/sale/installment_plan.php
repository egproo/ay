<?php
/**
 * خطط التقسيط للعملاء (Customer Installment Plans Controller)
 * 
 * الهدف: إدارة خطط التقسيط الفردية للعملاء
 * الميزات: إنشاء خطط، تتبع الأقساط، إدارة المدفوعات، تكامل محاسبي
 * التكامل: مع المبيعات والمحاسبة والعملاء والإشعارات
 * 
 * القيود المحاسبية:
 * - إنشاء خطة تقسيط: من ح/العملاء-تقسيط XXX إلى ح/المبيعات XXX وإلى ح/إيرادات فوائد مؤجلة XXX
 * - استحقاق قسط: من ح/أقساط مستحقة القبض XXX إلى ح/العملاء-تقسيط XXX
 * - تحصيل قسط: من ح/النقدية XXX إلى ح/أقساط مستحقة القبض XXX
 * - استحقاق فائدة: من ح/إيرادات فوائد مؤجلة XXX إلى ح/إيرادات فوائد تقسيط XXX
 * 
 * @author ERP Team
 * @version 1.0
 * @since 2024
 */

class ControllerSaleInstallmentPlan extends Controller {
    
    private $error = [];
    
    /**
     * الصفحة الرئيسية لخطط التقسيط
     */
    public function index() {
        $this->load->language('sale/installment_plan');
        
        $this->document->setTitle($this->language->get('heading_title'));
        
        $data['breadcrumbs'] = [];
        
        $data['breadcrumbs'][] = [
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        ];
        
        $data['breadcrumbs'][] = [
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('sale/installment_plan', 'user_token=' . $this->session->data['user_token'], true)
        ];
        
        $this->load->model('sale/installment_plan');
        
        // إعداد المرشحات
        $filter_data = $this->getFilterData();
        
        // الحصول على الخطط
        $results = $this->model_sale_installment_plan->getPlans($filter_data);
        $total = $this->model_sale_installment_plan->getTotalPlans($filter_data);
        
        $data['plans'] = [];
        
        foreach ($results as $result) {
            $data['plans'][] = [
                'plan_id' => $result['plan_id'],
                'customer_name' => $result['customer_name'],
                'customer_email' => $result['customer_email'],
                'template_name' => $result['template_name'],
                'total_amount' => number_format($result['total_amount'], 2),
                'down_payment' => number_format($result['down_payment'], 2),
                'financed_amount' => number_format($result['financed_amount'], 2),
                'installment_amount' => number_format($result['installment_amount'], 2),
                'installments_count' => $result['installments_count'],
                'paid_installments' => $result['paid_installments'],
                'remaining_installments' => $result['installments_count'] - $result['paid_installments'],
                'total_paid' => number_format($result['total_paid'], 2),
                'remaining_balance' => number_format($result['remaining_balance'], 2),
                'next_due_date' => $result['next_due_date'] ? date($this->language->get('date_format_short'), strtotime($result['next_due_date'])) : '-',
                'status' => $result['status'],
                'status_text' => $this->getPlanStatusText($result['status']),
                'status_class' => $this->getPlanStatusClass($result['status']),
                'overdue_amount' => number_format($result['overdue_amount'], 2),
                'overdue_days' => $result['overdue_days'],
                'date_created' => date($this->language->get('date_format_short'), strtotime($result['date_created'])),
                'view' => $this->url->link('sale/installment_plan/view', 'user_token=' . $this->session->data['user_token'] . '&plan_id=' . $result['plan_id'], true),
                'edit' => $this->url->link('sale/installment_plan/edit', 'user_token=' . $this->session->data['user_token'] . '&plan_id=' . $result['plan_id'], true),
                'payment' => $this->url->link('sale/installment_payment/add', 'user_token=' . $this->session->data['user_token'] . '&plan_id=' . $result['plan_id'], true),
                'schedule' => $this->url->link('sale/installment_plan/schedule', 'user_token=' . $this->session->data['user_token'] . '&plan_id=' . $result['plan_id'], true)
            ];
        }
        
        // إعداد الروابط والأزرار
        $data['add'] = $this->url->link('sale/installment_plan/add', 'user_token=' . $this->session->data['user_token'], true);
        $data['import'] = $this->url->link('sale/installment_plan/import', 'user_token=' . $this->session->data['user_token'], true);
        $data['export'] = $this->url->link('sale/installment_plan/export', 'user_token=' . $this->session->data['user_token'], true);
        $data['bulk_payment'] = $this->url->link('sale/installment_plan/bulkPayment', 'user_token=' . $this->session->data['user_token'], true);
        
        // إعداد التصفح
        $pagination = new Pagination();
        $pagination->total = $total;
        $pagination->page = $filter_data['page'];
        $pagination->limit = $filter_data['limit'];
        $pagination->url = $this->url->link('sale/installment_plan', 'user_token=' . $this->session->data['user_token'] . $this->getFilterUrl() . '&page={page}', true);
        
        $data['pagination'] = $pagination->render();
        $data['results'] = sprintf($this->language->get('text_pagination'), ($total) ? (($filter_data['page'] - 1) * $filter_data['limit']) + 1 : 0, ((($filter_data['page'] - 1) * $filter_data['limit']) > ($total - $filter_data['limit'])) ? $total : ((($filter_data['page'] - 1) * $filter_data['limit']) + $filter_data['limit']), $total, ceil($total / $filter_data['limit']));
        
        // إعداد المرشحات للعرض
        $data['filter_customer'] = $filter_data['filter_customer'];
        $data['filter_status'] = $filter_data['filter_status'];
        $data['filter_template'] = $filter_data['filter_template'];
        $data['filter_overdue'] = $filter_data['filter_overdue'];
        
        // إحصائيات سريعة
        $data['statistics'] = $this->getQuickStatistics();
        
        // قوائم للفلاتر
        $this->load->model('customer/customer');
        $this->load->model('sale/installment_template');
        $data['customers'] = $this->model_customer_customer->getCustomers(['limit' => 100]);
        $data['templates'] = $this->model_sale_installment_template->getTemplates(['filter_status' => '1']);
        
        $data['user_token'] = $this->session->data['user_token'];
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');
        
        $this->response->setOutput($this->load->view('sale/installment_plan_list', $data));
    }
    
    /**
     * إضافة خطة تقسيط جديدة
     */
    public function add() {
        $this->load->language('sale/installment_plan');
        
        $this->document->setTitle($this->language->get('heading_title_add'));
        
        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
            $this->load->model('sale/installment_plan');
            
            $plan_id = $this->model_sale_installment_plan->addPlan($this->request->post);
            
            // إضافة سجل في نشاط النظام
            $this->load->model('tool/activity_log');
            $this->model_tool_activity_log->addActivity('installment_plan_add', 'sale', 'تم إنشاء خطة تقسيط جديدة للعميل: ' . $this->request->post['customer_name'], $plan_id);
            
            // إرسال إشعار للعميل
            $this->sendCustomerNotification($plan_id, 'plan_created');
            
            $this->session->data['success'] = $this->language->get('text_success_add');
            
            $url = $this->getRedirectUrl();
            $this->response->redirect($this->url->link('sale/installment_plan', 'user_token=' . $this->session->data['user_token'] . $url, true));
        }
        
        $this->getForm();
    }
    
    /**
     * تعديل خطة تقسيط موجودة
     */
    public function edit() {
        $this->load->language('sale/installment_plan');
        
        $this->document->setTitle($this->language->get('heading_title_edit'));
        
        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
            $this->load->model('sale/installment_plan');
            
            $this->model_sale_installment_plan->editPlan($this->request->get['plan_id'], $this->request->post);
            
            // إضافة سجل في نشاط النظام
            $this->load->model('tool/activity_log');
            $this->model_tool_activity_log->addActivity('installment_plan_edit', 'sale', 'تم تعديل خطة تقسيط: ' . $this->request->post['customer_name'], $this->request->get['plan_id']);
            
            $this->session->data['success'] = $this->language->get('text_success_edit');
            
            $url = $this->getRedirectUrl();
            $this->response->redirect($this->url->link('sale/installment_plan', 'user_token=' . $this->session->data['user_token'] . $url, true));
        }
        
        $this->getForm();
    }
    
    /**
     * عرض تفاصيل خطة التقسيط
     */
    public function view() {
        $this->load->language('sale/installment_plan');
        
        if (isset($this->request->get['plan_id'])) {
            $plan_id = (int)$this->request->get['plan_id'];
            
            $this->load->model('sale/installment_plan');
            
            $plan_info = $this->model_sale_installment_plan->getPlan($plan_id);
            
            if ($plan_info) {
                $this->document->setTitle($this->language->get('heading_title_view') . ' - ' . $plan_info['customer_name']);
                
                // الحصول على جدول الأقساط
                $installments = $this->model_sale_installment_plan->getPlanInstallments($plan_id);
                
                // الحصول على تاريخ المدفوعات
                $payments = $this->model_sale_installment_plan->getPlanPayments($plan_id);
                
                $data['plan'] = $plan_info;
                $data['installments'] = $installments;
                $data['payments'] = $payments;
                
                // حساب الإحصائيات
                $data['plan_statistics'] = $this->calculatePlanStatistics($plan_info, $installments, $payments);
                
                $data['user_token'] = $this->session->data['user_token'];
                $data['header'] = $this->load->controller('common/header');
                $data['column_left'] = $this->load->controller('common/column_left');
                $data['footer'] = $this->load->controller('common/footer');
                
                $this->response->setOutput($this->load->view('sale/installment_plan_view', $data));
            } else {
                $this->session->data['error'] = $this->language->get('error_not_found');
                $this->response->redirect($this->url->link('sale/installment_plan', 'user_token=' . $this->session->data['user_token'], true));
            }
        } else {
            $this->response->redirect($this->url->link('sale/installment_plan', 'user_token=' . $this->session->data['user_token'], true));
        }
    }
    
    /**
     * عرض جدول الأقساط
     */
    public function schedule() {
        $this->load->language('sale/installment_plan');
        
        if (isset($this->request->get['plan_id'])) {
            $plan_id = (int)$this->request->get['plan_id'];
            
            $this->load->model('sale/installment_plan');
            
            $plan_info = $this->model_sale_installment_plan->getPlan($plan_id);
            
            if ($plan_info) {
                $this->document->setTitle($this->language->get('heading_title_schedule') . ' - ' . $plan_info['customer_name']);
                
                // الحصول على جدول الأقساط المفصل
                $installments = $this->model_sale_installment_plan->getPlanInstallments($plan_id);
                
                $data['plan'] = $plan_info;
                $data['installments'] = $installments;
                
                $data['user_token'] = $this->session->data['user_token'];
                $data['header'] = $this->load->controller('common/header');
                $data['column_left'] = $this->load->controller('common/column_left');
                $data['footer'] = $this->load->controller('common/footer');
                
                $this->response->setOutput($this->load->view('sale/installment_plan_schedule', $data));
            } else {
                $this->session->data['error'] = $this->language->get('error_not_found');
                $this->response->redirect($this->url->link('sale/installment_plan', 'user_token=' . $this->session->data['user_token'], true));
            }
        } else {
            $this->response->redirect($this->url->link('sale/installment_plan', 'user_token=' . $this->session->data['user_token'], true));
        }
    }
    
    /**
     * دوال مساعدة
     */
    private function getFilterData() {
        $filter_data = [
            'filter_customer' => $this->request->get['filter_customer'] ?? '',
            'filter_status' => $this->request->get['filter_status'] ?? '',
            'filter_template' => $this->request->get['filter_template'] ?? '',
            'filter_overdue' => $this->request->get['filter_overdue'] ?? '',
            'filter_amount_from' => $this->request->get['filter_amount_from'] ?? '',
            'filter_amount_to' => $this->request->get['filter_amount_to'] ?? '',
            'filter_date_from' => $this->request->get['filter_date_from'] ?? '',
            'filter_date_to' => $this->request->get['filter_date_to'] ?? '',
            'sort' => $this->request->get['sort'] ?? 'date_created',
            'order' => $this->request->get['order'] ?? 'DESC',
            'start' => ($this->request->get['page'] ?? 1 - 1) * ($this->request->get['limit'] ?? 20),
            'limit' => $this->request->get['limit'] ?? 20,
            'page' => $this->request->get['page'] ?? 1
        ];
        
        return $filter_data;
    }
    
    private function getPlanStatusText($status) {
        switch ($status) {
            case 'active':
                return $this->language->get('text_status_active');
            case 'completed':
                return $this->language->get('text_status_completed');
            case 'overdue':
                return $this->language->get('text_status_overdue');
            case 'defaulted':
                return $this->language->get('text_status_defaulted');
            case 'cancelled':
                return $this->language->get('text_status_cancelled');
            default:
                return $this->language->get('text_status_pending');
        }
    }
    
    private function getPlanStatusClass($status) {
        switch ($status) {
            case 'active':
                return 'success';
            case 'completed':
                return 'primary';
            case 'overdue':
                return 'warning';
            case 'defaulted':
                return 'danger';
            case 'cancelled':
                return 'default';
            default:
                return 'info';
        }
    }
    
    private function getQuickStatistics() {
        $this->load->model('sale/installment_plan');
        
        return [
            'total_plans' => $this->model_sale_installment_plan->getTotalPlans([]),
            'active_plans' => $this->model_sale_installment_plan->getTotalPlans(['filter_status' => 'active']),
            'overdue_plans' => $this->model_sale_installment_plan->getTotalPlans(['filter_status' => 'overdue']),
            'total_financed_amount' => $this->model_sale_installment_plan->getTotalFinancedAmount(),
            'total_collected' => $this->model_sale_installment_plan->getTotalCollected(),
            'total_outstanding' => $this->model_sale_installment_plan->getTotalOutstanding(),
            'overdue_amount' => $this->model_sale_installment_plan->getTotalOverdueAmount()
        ];
    }
    
    private function calculatePlanStatistics($plan, $installments, $payments) {
        $total_paid = array_sum(array_column($payments, 'amount'));
        $remaining_balance = $plan['financed_amount'] - $total_paid;
        
        $overdue_amount = 0;
        $next_due_date = null;
        
        foreach ($installments as $installment) {
            if ($installment['status'] == 'pending' && strtotime($installment['due_date']) < time()) {
                $overdue_amount += $installment['amount'];
            }
            
            if ($installment['status'] == 'pending' && !$next_due_date) {
                $next_due_date = $installment['due_date'];
            }
        }
        
        return [
            'total_paid' => $total_paid,
            'remaining_balance' => $remaining_balance,
            'overdue_amount' => $overdue_amount,
            'next_due_date' => $next_due_date,
            'completion_percentage' => ($plan['financed_amount'] > 0) ? ($total_paid / $plan['financed_amount']) * 100 : 0
        ];
    }
    
    private function sendCustomerNotification($plan_id, $type) {
        // إرسال إشعار للعميل (يمكن تطويره لاحقاً)
        // يمكن إرسال SMS أو Email أو إشعار داخل النظام
    }
    
    private function validateForm() {
        if (!$this->user->hasPermission('modify', 'sale/installment_plan')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }
        
        if (empty($this->request->post['customer_id'])) {
            $this->error['customer'] = $this->language->get('error_customer');
        }
        
        if (empty($this->request->post['template_id'])) {
            $this->error['template'] = $this->language->get('error_template');
        }
        
        if ($this->request->post['total_amount'] <= 0) {
            $this->error['amount'] = $this->language->get('error_amount');
        }
        
        return !$this->error;
    }
}
