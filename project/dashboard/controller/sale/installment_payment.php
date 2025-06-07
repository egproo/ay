<?php
/**
 * تسجيل مدفوعات الأقساط (Installment Payments Controller)
 *
 * الهدف: تسجيل ومتابعة مدفوعات أقساط العملاء
 * الميزات: تسجيل مدفوعات، معالجة متأخرات، خصومات، تكامل محاسبي
 * التكامل: مع خطط التقسيط والمحاسبة والإشعارات
 *
 * القيود المحاسبية:
 * - تحصيل قسط: من ح/النقدية أو البنوك XXX إلى ح/العملاء-تقسيط XXX
 * - غرامة تأخير: من ح/العملاء-تقسيط XXX إلى ح/إيرادات غرامات تأخير XXX
 * - خصم سداد مبكر: من ح/خصومات سداد مبكر XXX إلى ح/العملاء-تقسيط XXX
 * - استحقاق فائدة: من ح/إيرادات فوائد مؤجلة XXX إلى ح/إيرادات فوائد تقسيط XXX
 *
 * @author ERP Team
 * @version 2.0
 * @since 2024
 */

class ControllerSaleInstallmentPayment extends Controller {

    private $error = [];

    /**
     * الصفحة الرئيسية لمدفوعات الأقساط
     */
    public function index() {
        $this->load->language('sale/installment_payment');

        $this->document->setTitle($this->language->get('heading_title'));

        $data['breadcrumbs'] = [];

        $data['breadcrumbs'][] = [
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        ];

        $data['breadcrumbs'][] = [
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('sale/installment_payment', 'user_token=' . $this->session->data['user_token'], true)
        ];

        $this->load->model('sale/installment_payment');

        // إعداد المرشحات
        $filter_data = $this->getFilterData();

        // الحصول على المدفوعات
        $results = $this->model_sale_installment_payment->getPayments($filter_data);
        $total = $this->model_sale_installment_payment->getTotalPayments($filter_data);

        $data['payments'] = [];

        foreach ($results as $result) {
            $data['payments'][] = [
                'payment_id' => $result['payment_id'],
                'plan_id' => $result['plan_id'],
                'customer_name' => $result['customer_name'],
                'amount' => number_format($result['amount'], 2),
                'late_fee' => number_format($result['late_fee'], 2),
                'discount' => number_format($result['discount'], 2),
                'net_amount' => number_format($result['net_amount'], 2),
                'payment_method' => $this->getPaymentMethodText($result['payment_method']),
                'payment_date' => date($this->language->get('date_format_short'), strtotime($result['payment_date'])),
                'reference_number' => $result['reference_number'],
                'notes' => $result['notes'],
                'received_by_name' => $result['received_by_name'],
                'status' => $result['status'],
                'status_text' => $this->getPaymentStatusText($result['status']),
                'status_class' => $this->getPaymentStatusClass($result['status']),
                'date_created' => date($this->language->get('date_format_short'), strtotime($result['date_created'])),
                'view' => $this->url->link('sale/installment_payment/view', 'user_token=' . $this->session->data['user_token'] . '&payment_id=' . $result['payment_id'], true),
                'edit' => $this->url->link('sale/installment_payment/edit', 'user_token=' . $this->session->data['user_token'] . '&payment_id=' . $result['payment_id'], true),
                'receipt' => $this->url->link('sale/installment_payment/receipt', 'user_token=' . $this->session->data['user_token'] . '&payment_id=' . $result['payment_id'], true),
                'plan_details' => $this->url->link('sale/installment_plan/view', 'user_token=' . $this->session->data['user_token'] . '&plan_id=' . $result['plan_id'], true)
            ];
        }

        // إعداد الروابط والأزرار
        $data['add'] = $this->url->link('sale/installment_payment/add', 'user_token=' . $this->session->data['user_token'], true);
        $data['bulk_payment'] = $this->url->link('sale/installment_payment/bulkPayment', 'user_token=' . $this->session->data['user_token'], true);
        $data['export'] = $this->url->link('sale/installment_payment/export', 'user_token=' . $this->session->data['user_token'], true);
        $data['daily_report'] = $this->url->link('sale/installment_payment/dailyReport', 'user_token=' . $this->session->data['user_token'], true);

        // إعداد التصفح
        $pagination = new Pagination();
        $pagination->total = $total;
        $pagination->page = $filter_data['page'];
        $pagination->limit = $filter_data['limit'];
        $pagination->url = $this->url->link('sale/installment_payment', 'user_token=' . $this->session->data['user_token'] . $this->getFilterUrl() . '&page={page}', true);

        $data['pagination'] = $pagination->render();
        $data['results'] = sprintf($this->language->get('text_pagination'), ($total) ? (($filter_data['page'] - 1) * $filter_data['limit']) + 1 : 0, ((($filter_data['page'] - 1) * $filter_data['limit']) > ($total - $filter_data['limit'])) ? $total : ((($filter_data['page'] - 1) * $filter_data['limit']) + $filter_data['limit']), $total, ceil($total / $filter_data['limit']));

        // إعداد المرشحات للعرض
        $data['filter_customer'] = $filter_data['filter_customer'];
        $data['filter_payment_method'] = $filter_data['filter_payment_method'];
        $data['filter_status'] = $filter_data['filter_status'];
        $data['filter_date_from'] = $filter_data['filter_date_from'];
        $data['filter_date_to'] = $filter_data['filter_date_to'];

        // إحصائيات سريعة
        $data['statistics'] = $this->getQuickStatistics();

        // قوائم للفلاتر
        $this->load->model('customer/customer');
        $data['customers'] = $this->model_customer_customer->getCustomers(['limit' => 100]);
        $data['payment_methods'] = $this->getPaymentMethods();

        $data['user_token'] = $this->session->data['user_token'];
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('sale/installment_payment_list', $data));
    }

    /**
     * إضافة مدفوعة جديدة
     */
    public function add() {
        $this->load->language('sale/installment_payment');

        $this->document->setTitle($this->language->get('heading_title_add'));

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
            $this->load->model('sale/installment_payment');

            $payment_id = $this->model_sale_installment_payment->addPayment($this->request->post);

            // إضافة سجل في نشاط النظام
            $this->load->model('tool/activity_log');
            $this->model_tool_activity_log->addActivity('installment_payment_add', 'sale', 'تم تسجيل مدفوعة قسط بمبلغ: ' . $this->request->post['amount'], $payment_id);

            // إرسال إشعار للعميل
            $this->sendPaymentNotification($payment_id);

            $this->session->data['success'] = $this->language->get('text_success_add');

            // إعادة توجيه حسب الإجراء المطلوب
            if (isset($this->request->post['action']) && $this->request->post['action'] == 'save_and_print') {
                $this->response->redirect($this->url->link('sale/installment_payment/receipt', 'user_token=' . $this->session->data['user_token'] . '&payment_id=' . $payment_id, true));
            } else {
                $url = $this->getRedirectUrl();
                $this->response->redirect($this->url->link('sale/installment_payment', 'user_token=' . $this->session->data['user_token'] . $url, true));
            }
        }

        $this->getForm();
    }

    /**
     * تعديل مدفوعة موجودة
     */
    public function edit() {
        $this->load->language('sale/installment_payment');

        $this->document->setTitle($this->language->get('heading_title_edit'));

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
            $this->load->model('sale/installment_payment');

            $this->model_sale_installment_payment->editPayment($this->request->get['payment_id'], $this->request->post);

            // إضافة سجل في نشاط النظام
            $this->load->model('tool/activity_log');
            $this->model_tool_activity_log->addActivity('installment_payment_edit', 'sale', 'تم تعديل مدفوعة قسط', $this->request->get['payment_id']);

            $this->session->data['success'] = $this->language->get('text_success_edit');

            $url = $this->getRedirectUrl();
            $this->response->redirect($this->url->link('sale/installment_payment', 'user_token=' . $this->session->data['user_token'] . $url, true));
        }

        $this->getForm();
    }

    /**
     * عرض نموذج إضافة/تعديل دفعة تقسيطية
     */
    protected function getPaymentForm() {
        $data['heading_title'] = $this->language->get('heading_title');

        $data['text_form'] = isset($this->request->get['payment_id']) ? $this->language->get('text_edit_payment') : $this->language->get('text_add_payment');

        $data['entry_order_id'] = $this->language->get('entry_order_id');
        $data['entry_amount'] = $this->language->get('entry_amount');
        $data['entry_due_date'] = $this->language->get('entry_due_date');
        $data['entry_status'] = $this->language->get('entry_status');

        $data['button_save'] = isset($this->request->get['payment_id']) ? 'Save' : 'Add';
        $data['button_cancel'] = 'Cancel';

        // الأخطاء
        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }

        if (isset($this->error['order_id'])) {
            $data['error_order_id'] = $this->error['order_id'];
        } else {
            $data['error_order_id'] = '';
        }

        if (isset($this->error['amount'])) {
            $data['error_amount'] = $this->error['amount'];
        } else {
            $data['error_amount'] = '';
        }

        if (isset($this->error['due_date'])) {
            $data['error_due_date'] = $this->error['due_date'];
        } else {
            $data['error_due_date'] = '';
        }

        // إعداد الروابط
        if (isset($this->request->get['payment_id'])) {
            $data['action'] = $this->url->link('sale/installment_payment/edit', 'user_token=' . $this->session->data['user_token'] . '&payment_id=' . $this->request->get['payment_id'], true);
        } else {
            $data['action'] = $this->url->link('sale/installment_payment/add', 'user_token=' . $this->session->data['user_token'], true);
        }

        $data['cancel'] = $this->url->link('sale/installment_payment', 'user_token=' . $this->session->data['user_token'], true);

        // جلب بيانات الدفعة
        if (isset($this->request->get['payment_id']) && ($this->request->server['REQUEST_METHOD'] != 'POST')) {
            $payment_info = $this->model_sale_installment_payment->getPayment($this->request->get['payment_id']);
        }

        // تعبئة الحقول بالقيم الحالية أو المدخلة
        if (isset($this->request->post['order_id'])) {
            $data['order_id'] = $this->request->post['order_id'];
        } elseif (!empty($payment_info)) {
            $data['order_id'] = $payment_info['order_id'];
        } else {
            $data['order_id'] = '';
        }

        if (isset($this->request->post['amount'])) {
            $data['amount'] = $this->request->post['amount'];
        } elseif (!empty($payment_info)) {
            $data['amount'] = $payment_info['amount'];
        } else {
            $data['amount'] = '';
        }

        if (isset($this->request->post['due_date'])) {
            $data['due_date'] = $this->request->post['due_date'];
        } elseif (!empty($payment_info)) {
            $data['due_date'] = $payment_info['due_date'];
        } else {
            $data['due_date'] = '';
        }

        if (isset($this->request->post['status'])) {
            $data['status'] = $this->request->post['status'];
        } elseif (!empty($payment_info)) {
            $data['status'] = $payment_info['status'];
        } else {
            $data['status'] = 0;
        }

        // إعداد القوالب
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('sale/installment_payment_form', $data));
    }

    /**
     * دوال مساعدة
     */
    private function getFilterData() {
        $filter_data = [
            'filter_customer' => $this->request->get['filter_customer'] ?? '',
            'filter_payment_method' => $this->request->get['filter_payment_method'] ?? '',
            'filter_status' => $this->request->get['filter_status'] ?? '',
            'filter_amount_from' => $this->request->get['filter_amount_from'] ?? '',
            'filter_amount_to' => $this->request->get['filter_amount_to'] ?? '',
            'filter_date_from' => $this->request->get['filter_date_from'] ?? '',
            'filter_date_to' => $this->request->get['filter_date_to'] ?? '',
            'sort' => $this->request->get['sort'] ?? 'payment_date',
            'order' => $this->request->get['order'] ?? 'DESC',
            'start' => ($this->request->get['page'] ?? 1 - 1) * ($this->request->get['limit'] ?? 20),
            'limit' => $this->request->get['limit'] ?? 20,
            'page' => $this->request->get['page'] ?? 1
        ];

        return $filter_data;
    }

    private function getPaymentMethodText($method) {
        switch ($method) {
            case 'cash':
                return $this->language->get('text_cash');
            case 'bank_transfer':
                return $this->language->get('text_bank_transfer');
            case 'check':
                return $this->language->get('text_check');
            case 'credit_card':
                return $this->language->get('text_credit_card');
            case 'mobile_wallet':
                return $this->language->get('text_mobile_wallet');
            default:
                return $this->language->get('text_other');
        }
    }

    private function getPaymentStatusText($status) {
        switch ($status) {
            case 'confirmed':
                return $this->language->get('text_status_confirmed');
            case 'pending':
                return $this->language->get('text_status_pending');
            case 'cancelled':
                return $this->language->get('text_status_cancelled');
            default:
                return $this->language->get('text_status_pending');
        }
    }

    private function getPaymentStatusClass($status) {
        switch ($status) {
            case 'confirmed':
                return 'success';
            case 'pending':
                return 'warning';
            case 'cancelled':
                return 'danger';
            default:
                return 'info';
        }
    }

    private function getPaymentMethods() {
        return [
            ['value' => 'cash', 'text' => $this->language->get('text_cash')],
            ['value' => 'bank_transfer', 'text' => $this->language->get('text_bank_transfer')],
            ['value' => 'check', 'text' => $this->language->get('text_check')],
            ['value' => 'credit_card', 'text' => $this->language->get('text_credit_card')],
            ['value' => 'mobile_wallet', 'text' => $this->language->get('text_mobile_wallet')],
            ['value' => 'other', 'text' => $this->language->get('text_other')]
        ];
    }

    private function getQuickStatistics() {
        $this->load->model('sale/installment_payment');

        return [
            'today_payments' => $this->model_sale_installment_payment->getTodayPayments(),
            'today_amount' => $this->model_sale_installment_payment->getTodayAmount(),
            'month_payments' => $this->model_sale_installment_payment->getMonthPayments(),
            'month_amount' => $this->model_sale_installment_payment->getMonthAmount(),
            'pending_payments' => $this->model_sale_installment_payment->getPendingPayments(),
            'overdue_amount' => $this->model_sale_installment_payment->getOverdueAmount()
        ];
    }

    private function sendPaymentNotification($payment_id) {
        // إرسال إشعار للعميل (يمكن تطويره لاحقاً)
        // يمكن إرسال SMS أو Email أو إشعار داخل النظام
    }

    private function validateForm() {
        if (!$this->user->hasPermission('modify', 'sale/installment_payment')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        if (empty($this->request->post['plan_id'])) {
            $this->error['plan'] = $this->language->get('error_plan');
        }

        if ($this->request->post['amount'] <= 0) {
            $this->error['amount'] = $this->language->get('error_amount');
        }

        if (empty($this->request->post['payment_method'])) {
            $this->error['payment_method'] = $this->language->get('error_payment_method');
        }

        if (empty($this->request->post['payment_date'])) {
            $this->error['payment_date'] = $this->language->get('error_payment_date');
        }

        return !$this->error;
    }
}
