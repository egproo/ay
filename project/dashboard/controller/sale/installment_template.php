<?php
/**
 * قوالب خطط التقسيط (Installment Templates Controller)
 * 
 * الهدف: إدارة قوالب خطط التقسيط المختلفة
 * الميزات: قوالب متعددة، شروط مرنة، حساب الفوائد، تكامل محاسبي
 * التكامل: مع المبيعات والمحاسبة والعملاء
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

class ControllerSaleInstallmentTemplate extends Controller {
    
    private $error = [];
    
    /**
     * الصفحة الرئيسية لقوالب التقسيط
     */
    public function index() {
        $this->load->language('sale/installment_template');
        
        $this->document->setTitle($this->language->get('heading_title'));
        
        $data['breadcrumbs'] = [];
        
        $data['breadcrumbs'][] = [
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        ];
        
        $data['breadcrumbs'][] = [
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('sale/installment_template', 'user_token=' . $this->session->data['user_token'], true)
        ];
        
        $this->load->model('sale/installment_template');
        
        // إعداد المرشحات
        $filter_data = $this->getFilterData();
        
        // الحصول على القوالب
        $results = $this->model_sale_installment_template->getTemplates($filter_data);
        $total = $this->model_sale_installment_template->getTotalTemplates($filter_data);
        
        $data['templates'] = [];
        
        foreach ($results as $result) {
            $data['templates'][] = [
                'template_id' => $result['template_id'],
                'name' => $result['name'],
                'description' => $result['description'],
                'installments_count' => $result['installments_count'],
                'interest_rate' => number_format($result['interest_rate'], 2) . '%',
                'interest_type' => $this->getInterestTypeText($result['interest_type']),
                'min_amount' => number_format($result['min_amount'], 2),
                'max_amount' => number_format($result['max_amount'], 2),
                'down_payment_percentage' => number_format($result['down_payment_percentage'], 2) . '%',
                'status' => $result['status'],
                'status_text' => $result['status'] ? $this->language->get('text_enabled') : $this->language->get('text_disabled'),
                'status_class' => $result['status'] ? 'success' : 'danger',
                'date_added' => date($this->language->get('date_format_short'), strtotime($result['date_added'])),
                'edit' => $this->url->link('sale/installment_template/edit', 'user_token=' . $this->session->data['user_token'] . '&template_id=' . $result['template_id'], true),
                'copy' => $this->url->link('sale/installment_template/copy', 'user_token=' . $this->session->data['user_token'] . '&template_id=' . $result['template_id'], true),
                'preview' => $this->url->link('sale/installment_template/preview', 'user_token=' . $this->session->data['user_token'] . '&template_id=' . $result['template_id'], true)
            ];
        }
        
        // إعداد الروابط والأزرار
        $data['add'] = $this->url->link('sale/installment_template/add', 'user_token=' . $this->session->data['user_token'], true);
        $data['delete'] = $this->url->link('sale/installment_template/delete', 'user_token=' . $this->session->data['user_token'], true);
        $data['import'] = $this->url->link('sale/installment_template/import', 'user_token=' . $this->session->data['user_token'], true);
        $data['export'] = $this->url->link('sale/installment_template/export', 'user_token=' . $this->session->data['user_token'], true);
        
        // إعداد التصفح
        $pagination = new Pagination();
        $pagination->total = $total;
        $pagination->page = $filter_data['page'];
        $pagination->limit = $filter_data['limit'];
        $pagination->url = $this->url->link('sale/installment_template', 'user_token=' . $this->session->data['user_token'] . $this->getFilterUrl() . '&page={page}', true);
        
        $data['pagination'] = $pagination->render();
        $data['results'] = sprintf($this->language->get('text_pagination'), ($total) ? (($filter_data['page'] - 1) * $filter_data['limit']) + 1 : 0, ((($filter_data['page'] - 1) * $filter_data['limit']) > ($total - $filter_data['limit'])) ? $total : ((($filter_data['page'] - 1) * $filter_data['limit']) + $filter_data['limit']), $total, ceil($total / $filter_data['limit']));
        
        // إعداد المرشحات للعرض
        $data['filter_name'] = $filter_data['filter_name'];
        $data['filter_status'] = $filter_data['filter_status'];
        $data['filter_interest_type'] = $filter_data['filter_interest_type'];
        
        // إحصائيات سريعة
        $data['statistics'] = $this->getQuickStatistics();
        
        $data['user_token'] = $this->session->data['user_token'];
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');
        
        $this->response->setOutput($this->load->view('sale/installment_template_list', $data));
    }
    
    /**
     * إضافة قالب جديد
     */
    public function add() {
        $this->load->language('sale/installment_template');
        
        $this->document->setTitle($this->language->get('heading_title_add'));
        
        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
            $this->load->model('sale/installment_template');
            
            $template_id = $this->model_sale_installment_template->addTemplate($this->request->post);
            
            // إضافة سجل في نشاط النظام
            $this->load->model('tool/activity_log');
            $this->model_tool_activity_log->addActivity('installment_template_add', 'sale', 'تم إضافة قالب تقسيط جديد: ' . $this->request->post['name'], $template_id);
            
            $this->session->data['success'] = $this->language->get('text_success_add');
            
            $url = $this->getRedirectUrl();
            $this->response->redirect($this->url->link('sale/installment_template', 'user_token=' . $this->session->data['user_token'] . $url, true));
        }
        
        $this->getForm();
    }
    
    /**
     * تعديل قالب موجود
     */
    public function edit() {
        $this->load->language('sale/installment_template');
        
        $this->document->setTitle($this->language->get('heading_title_edit'));
        
        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
            $this->load->model('sale/installment_template');
            
            $this->model_sale_installment_template->editTemplate($this->request->get['template_id'], $this->request->post);
            
            // إضافة سجل في نشاط النظام
            $this->load->model('tool/activity_log');
            $this->model_tool_activity_log->addActivity('installment_template_edit', 'sale', 'تم تعديل قالب تقسيط: ' . $this->request->post['name'], $this->request->get['template_id']);
            
            $this->session->data['success'] = $this->language->get('text_success_edit');
            
            $url = $this->getRedirectUrl();
            $this->response->redirect($this->url->link('sale/installment_template', 'user_token=' . $this->session->data['user_token'] . $url, true));
        }
        
        $this->getForm();
    }
    
    /**
     * حذف قوالب
     */
    public function delete() {
        $this->load->language('sale/installment_template');
        
        if (isset($this->request->post['selected']) && $this->validateDelete()) {
            $this->load->model('sale/installment_template');
            
            foreach ($this->request->post['selected'] as $template_id) {
                // التحقق من وجود خطط تقسيط مرتبطة
                if ($this->model_sale_installment_template->hasInstallmentPlans($template_id)) {
                    $this->error['warning'] = $this->language->get('error_has_plans');
                    break;
                }
                
                $this->model_sale_installment_template->deleteTemplate($template_id);
                
                // إضافة سجل في نشاط النظام
                $this->load->model('tool/activity_log');
                $this->model_tool_activity_log->addActivity('installment_template_delete', 'sale', 'تم حذف قالب تقسيط', $template_id);
            }
            
            if (!$this->error) {
                $this->session->data['success'] = $this->language->get('text_success_delete');
            }
        }
        
        $this->getList();
    }
    
    /**
     * نسخ قالب
     */
    public function copy() {
        $this->load->language('sale/installment_template');
        
        if (isset($this->request->get['template_id']) && $this->validateCopy()) {
            $this->load->model('sale/installment_template');
            
            $template_info = $this->model_sale_installment_template->getTemplate($this->request->get['template_id']);
            
            if ($template_info) {
                // تعديل البيانات للنسخة الجديدة
                $template_info['name'] = $template_info['name'] . ' - نسخة';
                $template_info['status'] = 0; // تعطيل النسخة الجديدة افتراضياً
                
                $new_template_id = $this->model_sale_installment_template->addTemplate($template_info);
                
                $this->session->data['success'] = $this->language->get('text_success_copy');
            }
            
            $this->response->redirect($this->url->link('sale/installment_template', 'user_token=' . $this->session->data['user_token'], true));
        }
        
        $this->getList();
    }
    
    /**
     * معاينة قالب التقسيط
     */
    public function preview() {
        $this->load->language('sale/installment_template');
        
        if (isset($this->request->get['template_id'])) {
            $template_id = (int)$this->request->get['template_id'];
            
            $this->load->model('sale/installment_template');
            
            $template_info = $this->model_sale_installment_template->getTemplate($template_id);
            
            if ($template_info) {
                $this->document->setTitle($this->language->get('heading_title_preview') . ' - ' . $template_info['name']);
                
                // حساب مثال للتقسيط
                $example_amount = 10000; // مبلغ افتراضي للمثال
                $installment_schedule = $this->calculateInstallmentSchedule($template_info, $example_amount);
                
                $data['template'] = $template_info;
                $data['example_amount'] = $example_amount;
                $data['installment_schedule'] = $installment_schedule;
                
                $data['user_token'] = $this->session->data['user_token'];
                $data['header'] = $this->load->controller('common/header');
                $data['column_left'] = $this->load->controller('common/column_left');
                $data['footer'] = $this->load->controller('common/footer');
                
                $this->response->setOutput($this->load->view('sale/installment_template_preview', $data));
            } else {
                $this->session->data['error'] = $this->language->get('error_not_found');
                $this->response->redirect($this->url->link('sale/installment_template', 'user_token=' . $this->session->data['user_token'], true));
            }
        } else {
            $this->response->redirect($this->url->link('sale/installment_template', 'user_token=' . $this->session->data['user_token'], true));
        }
    }
    
    /**
     * دوال مساعدة
     */
    private function getFilterData() {
        $filter_data = [
            'filter_name' => $this->request->get['filter_name'] ?? '',
            'filter_status' => $this->request->get['filter_status'] ?? '',
            'filter_interest_type' => $this->request->get['filter_interest_type'] ?? '',
            'sort' => $this->request->get['sort'] ?? 'name',
            'order' => $this->request->get['order'] ?? 'ASC',
            'start' => ($this->request->get['page'] ?? 1 - 1) * ($this->request->get['limit'] ?? 20),
            'limit' => $this->request->get['limit'] ?? 20,
            'page' => $this->request->get['page'] ?? 1
        ];
        
        return $filter_data;
    }
    
    private function getInterestTypeText($type) {
        switch ($type) {
            case 'fixed':
                return $this->language->get('text_fixed_interest');
            case 'reducing':
                return $this->language->get('text_reducing_interest');
            case 'simple':
                return $this->language->get('text_simple_interest');
            default:
                return $this->language->get('text_no_interest');
        }
    }
    
    private function getQuickStatistics() {
        $this->load->model('sale/installment_template');
        
        return [
            'total_templates' => $this->model_sale_installment_template->getTotalTemplates([]),
            'active_templates' => $this->model_sale_installment_template->getTotalTemplates(['filter_status' => '1']),
            'most_used_template' => $this->model_sale_installment_template->getMostUsedTemplate(),
            'avg_interest_rate' => $this->model_sale_installment_template->getAverageInterestRate()
        ];
    }
    
    private function calculateInstallmentSchedule($template, $amount) {
        // حساب جدول الأقساط بناءً على القالب
        $schedule = [];
        
        $down_payment = $amount * ($template['down_payment_percentage'] / 100);
        $financed_amount = $amount - $down_payment;
        
        // حساب الفائدة والأقساط حسب نوع الفائدة
        switch ($template['interest_type']) {
            case 'fixed':
                $total_interest = $financed_amount * ($template['interest_rate'] / 100);
                $total_amount = $financed_amount + $total_interest;
                $installment_amount = $total_amount / $template['installments_count'];
                break;
                
            case 'reducing':
                // حساب الفائدة المتناقصة
                $monthly_rate = ($template['interest_rate'] / 100) / 12;
                $installment_amount = $financed_amount * ($monthly_rate * pow(1 + $monthly_rate, $template['installments_count'])) / (pow(1 + $monthly_rate, $template['installments_count']) - 1);
                break;
                
            default:
                $installment_amount = $financed_amount / $template['installments_count'];
        }
        
        // إنشاء جدول الأقساط
        $remaining_balance = $financed_amount;
        
        for ($i = 1; $i <= $template['installments_count']; $i++) {
            $due_date = date('Y-m-d', strtotime('+' . $i . ' months'));
            
            if ($template['interest_type'] == 'reducing') {
                $interest_amount = $remaining_balance * ($template['interest_rate'] / 100) / 12;
                $principal_amount = $installment_amount - $interest_amount;
            } else {
                $interest_amount = ($total_interest ?? 0) / $template['installments_count'];
                $principal_amount = $installment_amount - $interest_amount;
            }
            
            $schedule[] = [
                'installment_number' => $i,
                'due_date' => $due_date,
                'installment_amount' => number_format($installment_amount, 2),
                'principal_amount' => number_format($principal_amount, 2),
                'interest_amount' => number_format($interest_amount, 2),
                'remaining_balance' => number_format($remaining_balance - $principal_amount, 2)
            ];
            
            $remaining_balance -= $principal_amount;
        }
        
        return $schedule;
    }
    
    private function validateForm() {
        if (!$this->user->hasPermission('modify', 'sale/installment_template')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }
        
        if ((utf8_strlen(trim($this->request->post['name'])) < 1) || (utf8_strlen($this->request->post['name']) > 255)) {
            $this->error['name'] = $this->language->get('error_name');
        }
        
        if ($this->request->post['installments_count'] < 1 || $this->request->post['installments_count'] > 120) {
            $this->error['installments_count'] = $this->language->get('error_installments_count');
        }
        
        if ($this->request->post['interest_rate'] < 0 || $this->request->post['interest_rate'] > 100) {
            $this->error['interest_rate'] = $this->language->get('error_interest_rate');
        }
        
        return !$this->error;
    }
    
    private function validateDelete() {
        if (!$this->user->hasPermission('modify', 'sale/installment_template')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }
        
        return !$this->error;
    }
    
    private function validateCopy() {
        if (!$this->user->hasPermission('modify', 'sale/installment_template')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }
        
        return !$this->error;
    }
}
