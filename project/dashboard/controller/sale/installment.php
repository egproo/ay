<?php
class ControllerSaleInstallment extends Controller {
    private $error = array();

    public function index() {
        $this->load->language('sale/installment');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('sale/installment');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
            if (isset($this->request->post['selected'])) {
                $this->model_sale_installment->deleteInstallmentPlans($this->request->post['selected']);
                $this->session->data['success'] = $this->language->get('text_success');
            }

            $this->response->redirect($this->url->link('sale/installment', 'user_token=' . $this->session->data['user_token'], true));
        }

        // إعداد البيانات للعرض
        $data['heading_title'] = $this->language->get('heading_title');
        $data['text_list'] = $this->language->get('text_list');
        $data['text_no_results'] = 'لا توجد خطط تقسيط.';
        $data['text_confirm'] = 'هل أنت متأكد؟';

        $data['column_name'] = $this->language->get('column_name');
        $data['column_total_amount'] = $this->language->get('column_total_amount');
        $data['column_number_of_installments'] = $this->language->get('column_number_of_installments');
        $data['column_interest_rate'] = $this->language->get('column_interest_rate');
        $data['column_status'] = $this->language->get('column_status');
        $data['column_action'] = $this->language->get('column_action');

        $data['entry_name'] = $this->language->get('entry_name');
        $data['entry_description'] = $this->language->get('entry_description');
        $data['entry_total_amount'] = $this->language->get('entry_total_amount');
        $data['entry_number_of_installments'] = $this->language->get('entry_number_of_installments');
        $data['entry_interest_rate'] = $this->language->get('entry_interest_rate');
        $data['entry_status'] = $this->language->get('entry_status');

        $data['button_add'] = $this->language->get('button_add_plan');
        $data['button_delete'] = $this->language->get('button_delete');

        // الأخطاء
        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }

        // نجاح
        if (isset($this->session->data['success'])) {
            $data['success'] = $this->session->data['success'];
            unset($this->session->data['success']);
        } else {
            $data['success'] = '';
        }

        // إعداد الروابط
        $data['add'] = $this->url->link('sale/installment/add', 'user_token=' . $this->session->data['user_token'], true);
        $data['delete'] = $this->url->link('sale/installment', 'user_token=' . $this->session->data['user_token'], true);

        // جلب خطط التقسيط
        $plans = $this->model_sale_installment->getInstallmentPlans();
        $data['plans'] = array();

        foreach ($plans as $plan) {
            $data['plans'][] = array(
                'plan_id' => $plan['plan_id'],
                'name' => $plan['name'],
                'total_amount' => $plan['total_amount'],
                'number_of_installments' => $plan['number_of_installments'],
                'interest_rate' => $plan['interest_rate'],
                'status' => ($plan['status']) ? 'Enabled' : 'Disabled',
                'edit' => $this->url->link('sale/installment/edit', 'user_token=' . $this->session->data['user_token'] . '&plan_id=' . $plan['plan_id'], true)
            );
        }

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('sale/installment_list', $data));
    }

    /**
     * إضافة خطة تقسيط جديدة
     */
    public function add() {
        $this->load->language('sale/installment');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('sale/installment');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
            $this->model_sale_installment->addInstallmentPlan($this->request->post);

            $this->session->data['success'] = $this->language->get('text_success');

            $this->response->redirect($this->url->link('sale/installment', 'user_token=' . $this->session->data['user_token'], true));
        }

        $this->getForm();
    }

    /**
     * تعديل خطة تقسيط موجودة
     */
    public function edit() {
        $this->load->language('sale/installment');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('sale/installment');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
            $this->model_sale_installment->editInstallmentPlan($this->request->get['plan_id'], $this->request->post);

            $this->session->data['success'] = $this->language->get('text_success');

            $this->response->redirect($this->url->link('sale/installment', 'user_token=' . $this->session->data['user_token'], true));
        }

        $this->getForm();
    }

    /**
     * حذف خطط تقسيط
     */
    public function delete() {
        $this->load->language('sale/installment');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('sale/installment');

        if (isset($this->request->post['selected']) && $this->validate()) {
            $this->model_sale_installment->deleteInstallmentPlans($this->request->post['selected']);

            $this->session->data['success'] = $this->language->get('text_success');

            $this->response->redirect($this->url->link('sale/installment', 'user_token=' . $this->session->data['user_token'], true));
        }

        $this->index();
    }

    /**
     * عرض نموذج إضافة/تعديل خطة تقسيط
     */
    protected function getForm() {
        $data['heading_title'] = $this->language->get('heading_title');

        $data['text_form'] = isset($this->request->get['plan_id']) ? $this->language->get('text_edit') : $this->language->get('text_add');

        $data['entry_name'] = $this->language->get('entry_name');
        $data['entry_description'] = $this->language->get('entry_description');
        $data['entry_total_amount'] = $this->language->get('entry_total_amount');
        $data['entry_number_of_installments'] = $this->language->get('entry_number_of_installments');
        $data['entry_interest_rate'] = $this->language->get('entry_interest_rate');
        $data['entry_status'] = $this->language->get('entry_status');

        $data['button_save'] = isset($this->request->get['plan_id']) ? 'Save' : 'Add';
        $data['button_cancel'] = 'Cancel';

        // الأخطاء
        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }

        if (isset($this->error['name'])) {
            $data['error_name'] = $this->error['name'];
        } else {
            $data['error_name'] = '';
        }

        if (isset($this->error['total_amount'])) {
            $data['error_total_amount'] = $this->error['total_amount'];
        } else {
            $data['error_total_amount'] = '';
        }

        if (isset($this->error['number_of_installments'])) {
            $data['error_number_of_installments'] = $this->error['number_of_installments'];
        } else {
            $data['error_number_of_installments'] = '';
        }

        if (isset($this->error['interest_rate'])) {
            $data['error_interest_rate'] = $this->error['interest_rate'];
        } else {
            $data['error_interest_rate'] = '';
        }

        // إعداد الروابط
        if (isset($this->request->get['plan_id'])) {
            $data['action'] = $this->url->link('sale/installment/edit', 'user_token=' . $this->session->data['user_token'] . '&plan_id=' . $this->request->get['plan_id'], true);
        } else {
            $data['action'] = $this->url->link('sale/installment/add', 'user_token=' . $this->session->data['user_token'], true);
        }

        $data['cancel'] = $this->url->link('sale/installment', 'user_token=' . $this->session->data['user_token'], true);

        // جلب بيانات الخطة
        if (isset($this->request->get['plan_id']) && ($this->request->server['REQUEST_METHOD'] != 'POST')) {
            $plan_info = $this->model_sale_installment->getInstallmentPlan($this->request->get['plan_id']);
        }

        // تعبئة الحقول بالقيم الحالية أو المدخلة
        if (isset($this->request->post['name'])) {
            $data['name'] = $this->request->post['name'];
        } elseif (!empty($plan_info)) {
            $data['name'] = $plan_info['name'];
        } else {
            $data['name'] = '';
        }

        if (isset($this->request->post['description'])) {
            $data['description'] = $this->request->post['description'];
        } elseif (!empty($plan_info)) {
            $data['description'] = $plan_info['description'];
        } else {
            $data['description'] = '';
        }

        if (isset($this->request->post['total_amount'])) {
            $data['total_amount'] = $this->request->post['total_amount'];
        } elseif (!empty($plan_info)) {
            $data['total_amount'] = $plan_info['total_amount'];
        } else {
            $data['total_amount'] = '';
        }

        if (isset($this->request->post['number_of_installments'])) {
            $data['number_of_installments'] = $this->request->post['number_of_installments'];
        } elseif (!empty($plan_info)) {
            $data['number_of_installments'] = $plan_info['number_of_installments'];
        } else {
            $data['number_of_installments'] = '';
        }

        if (isset($this->request->post['interest_rate'])) {
            $data['interest_rate'] = $this->request->post['interest_rate'];
        } elseif (!empty($plan_info)) {
            $data['interest_rate'] = $plan_info['interest_rate'];
        } else {
            $data['interest_rate'] = '';
        }

        if (isset($this->request->post['status'])) {
            $data['status'] = $this->request->post['status'];
        } elseif (!empty($plan_info)) {
            $data['status'] = $plan_info['status'];
        } else {
            $data['status'] = 1;
        }

        // إعداد القوالب
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('sale/installment_form', $data));
    }

    /**
     * التحقق من صلاحيات المستخدم
     */
    protected function validate() {
        if (!$this->user->hasPermission('modify', 'sale/installment')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        return !$this->error;
    }

    /**
     * التحقق من صحة بيانات النموذج
     */
    protected function validateForm() {
        if (!$this->user->hasPermission('modify', 'sale/installment')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        if ((utf8_strlen($this->request->post['name']) < 1) || (utf8_strlen($this->request->post['name']) > 255)) {
            $this->error['name'] = $this->language->get('error_name');
        }

        if (empty($this->request->post['total_amount']) || !is_numeric($this->request->post['total_amount'])) {
            $this->error['total_amount'] = $this->language->get('error_total_amount');
        }

        if (empty($this->request->post['number_of_installments']) || !is_numeric($this->request->post['number_of_installments'])) {
            $this->error['number_of_installments'] = $this->language->get('error_number_of_installments');
        }

        if (!isset($this->request->post['interest_rate']) || !is_numeric($this->request->post['interest_rate'])) {
            $this->error['interest_rate'] = $this->language->get('error_interest_rate');
        }

        return !$this->error;
    }
}
