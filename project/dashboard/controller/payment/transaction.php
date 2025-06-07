<?php
/**
 * تحكم معاملات بوابات الدفع المحسن
 * يدعم إدارة جميع معاملات بوابات الدفع مع التكامل المحاسبي الكامل
 */
class ControllerPaymentTransaction extends Controller {
    private $error = array();

    public function index() {
        $this->load->language('payment/transaction');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('payment/transaction');
        $this->getList();
    }

    public function add() {
        $this->load->language('payment/transaction');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('payment/transaction');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
            $transaction_id = $this->model_payment_transaction->addTransaction($this->request->post);
            $this->session->data['success'] = $this->language->get('text_success');

            $url = '';
            if (isset($this->request->get['sort'])) {
                $url .= '&sort=' . $this->request->get['sort'];
            }
            if (isset($this->request->get['order'])) {
                $url .= '&order=' . $this->request->get['order'];
            }
            if (isset($this->request->get['page'])) {
                $url .= '&page=' . $this->request->get['page'];
            }

            $this->response->redirect($this->url->link('payment/transaction', 'user_token=' . $this->session->data['user_token'] . $url, true));
        }

        $this->getForm();
    }

    public function edit() {
        $this->load->language('payment/transaction');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('payment/transaction');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
            $this->model_payment_transaction->editTransaction($this->request->get['transaction_id'], $this->request->post);
            $this->session->data['success'] = $this->language->get('text_success');

            $url = '';
            if (isset($this->request->get['sort'])) {
                $url .= '&sort=' . $this->request->get['sort'];
            }
            if (isset($this->request->get['order'])) {
                $url .= '&order=' . $this->request->get['order'];
            }
            if (isset($this->request->get['page'])) {
                $url .= '&page=' . $this->request->get['page'];
            }

            $this->response->redirect($this->url->link('payment/transaction', 'user_token=' . $this->session->data['user_token'] . $url, true));
        }

        $this->getForm();
    }

    public function delete() {
        $this->load->language('payment/transaction');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('payment/transaction');

        if (isset($this->request->post['selected']) && $this->validateDelete()) {
            foreach ($this->request->post['selected'] as $transaction_id) {
                $this->model_payment_transaction->deleteTransaction($transaction_id);
            }

            $this->session->data['success'] = $this->language->get('text_success');

            $url = '';
            if (isset($this->request->get['sort'])) {
                $url .= '&sort=' . $this->request->get['sort'];
            }
            if (isset($this->request->get['order'])) {
                $url .= '&order=' . $this->request->get['order'];
            }
            if (isset($this->request->get['page'])) {
                $url .= '&page=' . $this->request->get['page'];
            }

            $this->response->redirect($this->url->link('payment/transaction', 'user_token=' . $this->session->data['user_token'] . $url, true));
        }

        $this->getList();
    }

    public function settle() {
        $this->load->language('payment/transaction');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('payment/transaction');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateSettle()) {
            $result = $this->model_payment_transaction->settleTransaction($this->request->post['transaction_id'], $this->request->post);
            
            if ($result['success']) {
                $this->session->data['success'] = $this->language->get('text_success_settled');
            } else {
                $this->session->data['error'] = $result['error'];
            }

            $this->response->redirect($this->url->link('payment/transaction', 'user_token=' . $this->session->data['user_token'], true));
        }

        $this->getSettleForm();
    }

    public function refund() {
        $this->load->language('payment/transaction');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('payment/transaction');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateRefund()) {
            $result = $this->model_payment_transaction->refundTransaction($this->request->post['transaction_id'], $this->request->post);
            
            if ($result['success']) {
                $this->session->data['success'] = $this->language->get('text_success_refunded');
            } else {
                $this->session->data['error'] = $result['error'];
            }

            $this->response->redirect($this->url->link('payment/transaction', 'user_token=' . $this->session->data['user_token'], true));
        }

        $this->getRefundForm();
    }

    public function report() {
        $this->load->language('payment/transaction');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('payment/transaction');
        $this->getReport();
    }

    protected function getList() {
        if (isset($this->request->get['filter_gateway_id'])) {
            $filter_gateway_id = $this->request->get['filter_gateway_id'];
        } else {
            $filter_gateway_id = '';
        }

        if (isset($this->request->get['filter_status'])) {
            $filter_status = $this->request->get['filter_status'];
        } else {
            $filter_status = '';
        }

        if (isset($this->request->get['filter_transaction_type'])) {
            $filter_transaction_type = $this->request->get['filter_transaction_type'];
        } else {
            $filter_transaction_type = '';
        }

        if (isset($this->request->get['filter_external_id'])) {
            $filter_external_id = $this->request->get['filter_external_id'];
        } else {
            $filter_external_id = '';
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

        if (isset($this->request->get['sort'])) {
            $sort = $this->request->get['sort'];
        } else {
            $sort = 'transaction_date';
        }

        if (isset($this->request->get['order'])) {
            $order = $this->request->get['order'];
        } else {
            $order = 'DESC';
        }

        if (isset($this->request->get['page'])) {
            $page = (int)$this->request->get['page'];
        } else {
            $page = 1;
        }

        $data['breadcrumbs'] = array();
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('payment/transaction', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['add'] = $this->url->link('payment/transaction/add', 'user_token=' . $this->session->data['user_token'], true);
        $data['delete'] = $this->url->link('payment/transaction/delete', 'user_token=' . $this->session->data['user_token'], true);
        $data['report'] = $this->url->link('payment/transaction/report', 'user_token=' . $this->session->data['user_token'], true);

        $data['transactions'] = array();

        $filter_data = array(
            'filter_gateway_id' => $filter_gateway_id,
            'filter_status' => $filter_status,
            'filter_transaction_type' => $filter_transaction_type,
            'filter_external_id' => $filter_external_id,
            'filter_date_start' => $filter_date_start,
            'filter_date_end' => $filter_date_end,
            'sort' => $sort,
            'order' => $order,
            'start' => ($page - 1) * $this->config->get('config_limit_admin'),
            'limit' => $this->config->get('config_limit_admin')
        );

        $transaction_total = $this->model_payment_transaction->getTotalTransactions($filter_data);
        $results = $this->model_payment_transaction->getTransactions($filter_data);

        foreach ($results as $result) {
            $data['transactions'][] = array(
                'transaction_id' => $result['transaction_id'],
                'external_transaction_id' => $result['external_transaction_id'],
                'gateway_name' => $result['gateway_name'],
                'provider_name' => $result['provider_name'],
                'transaction_type' => $result['transaction_type'],
                'amount' => $this->currency->format($result['amount'], $result['currency_code']),
                'commission_amount' => $this->currency->format($result['commission_amount'], $result['currency_code']),
                'net_amount' => $this->currency->format($result['net_amount'], $result['currency_code']),
                'status' => $result['status'],
                'status_text' => $this->language->get('text_status_' . $result['status']),
                'transaction_date' => date($this->language->get('datetime_format'), strtotime($result['transaction_date'])),
                'customer_name' => $result['customer_name'],
                'customer_reference' => $result['customer_reference'],
                'order_id' => $result['order_id'],
                'edit' => $this->url->link('payment/transaction/edit', 'user_token=' . $this->session->data['user_token'] . '&transaction_id=' . $result['transaction_id'], true),
                'settle' => $this->url->link('payment/transaction/settle', 'user_token=' . $this->session->data['user_token'] . '&transaction_id=' . $result['transaction_id'], true),
                'refund' => $this->url->link('payment/transaction/refund', 'user_token=' . $this->session->data['user_token'] . '&transaction_id=' . $result['transaction_id'], true),
                'view_order' => $result['order_id'] ? $this->url->link('sale/order/info', 'user_token=' . $this->session->data['user_token'] . '&order_id=' . $result['order_id'], true) : ''
            );
        }

        // الحصول على بوابات الدفع للفلترة
        $this->load->model('payment/gateway');
        $data['gateways'] = $this->model_payment_gateway->getGateways();

        // أنواع المعاملات
        $data['transaction_types'] = array(
            'payment' => $this->language->get('text_payment'),
            'refund' => $this->language->get('text_refund'),
            'chargeback' => $this->language->get('text_chargeback'),
            'settlement' => $this->language->get('text_settlement')
        );

        // حالات المعاملات
        $data['statuses'] = array(
            'pending' => $this->language->get('text_pending'),
            'processing' => $this->language->get('text_processing'),
            'completed' => $this->language->get('text_completed'),
            'failed' => $this->language->get('text_failed'),
            'cancelled' => $this->language->get('text_cancelled'),
            'settled' => $this->language->get('text_settled'),
            'refunded' => $this->language->get('text_refunded'),
            'partially_refunded' => $this->language->get('text_partially_refunded')
        );

        // إضافة باقي بيانات العرض
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

        if (isset($this->session->data['error'])) {
            $data['error_warning'] = $this->session->data['error'];
            unset($this->session->data['error']);
        }

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('payment/transaction_list', $data));
    }

    protected function getForm() {
        $data['text_form'] = !isset($this->request->get['transaction_id']) ? $this->language->get('text_add') : $this->language->get('text_edit');

        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }

        $data['breadcrumbs'] = array();
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('payment/transaction', 'user_token=' . $this->session->data['user_token'], true)
        );

        if (!isset($this->request->get['transaction_id'])) {
            $data['action'] = $this->url->link('payment/transaction/add', 'user_token=' . $this->session->data['user_token'], true);
        } else {
            $data['action'] = $this->url->link('payment/transaction/edit', 'user_token=' . $this->session->data['user_token'] . '&transaction_id=' . $this->request->get['transaction_id'], true);
        }

        $data['cancel'] = $this->url->link('payment/transaction', 'user_token=' . $this->session->data['user_token'], true);

        if (isset($this->request->get['transaction_id']) && ($this->request->server['REQUEST_METHOD'] != 'POST')) {
            $transaction_info = $this->model_payment_transaction->getTransaction($this->request->get['transaction_id']);
        }

        // الحصول على بوابات الدفع
        $this->load->model('payment/gateway');
        $data['gateways'] = $this->model_payment_gateway->getGateways();

        // الحصول على العملاء
        $this->load->model('customer/customer');
        $data['customers'] = $this->model_customer_customer->getCustomers();

        // بيانات النموذج
        $fields = ['gateway_id', 'external_transaction_id', 'transaction_type', 'amount', 'commission_amount', 
                   'net_amount', 'currency_code', 'status', 'transaction_date', 'customer_reference', 
                   'order_id', 'customer_id', 'description', 'metadata'];
        
        foreach ($fields as $field) {
            if (isset($this->request->post[$field])) {
                $data[$field] = $this->request->post[$field];
            } elseif (!empty($transaction_info)) {
                $data[$field] = $transaction_info[$field];
            } else {
                $data[$field] = ($field == 'transaction_date') ? date('Y-m-d H:i:s') : 
                               (($field == 'currency_code') ? $this->config->get('config_currency') : '');
            }
        }

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('payment/transaction_form', $data));
    }

    protected function getReport() {
        $date_start = $this->request->get['date_start'] ?? date('Y-m-01');
        $date_end = $this->request->get['date_end'] ?? date('Y-m-d');
        $gateway_id = $this->request->get['gateway_id'] ?? '';

        $data['breadcrumbs'] = array();
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('payment/transaction', 'user_token=' . $this->session->data['user_token'], true)
        );
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_report'),
            'href' => $this->url->link('payment/transaction/report', 'user_token=' . $this->session->data['user_token'], true)
        );

        // إنشاء تقرير المعاملات
        $report_data = $this->generateTransactionReport($date_start, $date_end, $gateway_id);

        $data['report'] = $report_data;
        $data['date_start'] = $date_start;
        $data['date_end'] = $date_end;
        $data['gateway_id'] = $gateway_id;

        // الحصول على بوابات الدفع للفلترة
        $this->load->model('payment/gateway');
        $data['gateways'] = $this->model_payment_gateway->getGateways();

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('payment/transaction_report', $data));
    }

    private function generateTransactionReport($date_start, $date_end, $gateway_id = '') {
        $filter_data = array(
            'filter_date_start' => $date_start,
            'filter_date_end' => $date_end
        );

        if ($gateway_id) {
            $filter_data['filter_gateway_id'] = $gateway_id;
        }

        $transactions = $this->model_payment_transaction->getTransactions($filter_data);

        $summary = array(
            'total_transactions' => count($transactions),
            'total_amount' => 0,
            'total_commission' => 0,
            'total_net_amount' => 0,
            'successful_transactions' => 0,
            'failed_transactions' => 0,
            'refunded_transactions' => 0
        );

        foreach ($transactions as $transaction) {
            $summary['total_amount'] += $transaction['amount'];
            $summary['total_commission'] += $transaction['commission_amount'];
            $summary['total_net_amount'] += $transaction['net_amount'];

            if ($transaction['status'] == 'completed') {
                $summary['successful_transactions']++;
            } elseif (in_array($transaction['status'], ['failed', 'cancelled'])) {
                $summary['failed_transactions']++;
            } elseif (in_array($transaction['status'], ['refunded', 'partially_refunded'])) {
                $summary['refunded_transactions']++;
            }
        }

        $summary['success_rate'] = $summary['total_transactions'] > 0 ? 
            ($summary['successful_transactions'] / $summary['total_transactions']) * 100 : 0;

        return array(
            'summary' => $summary,
            'transactions' => $transactions
        );
    }

    protected function validateForm() {
        if (!$this->user->hasPermission('modify', 'payment/transaction')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        if (empty($this->request->post['gateway_id'])) {
            $this->error['gateway_id'] = $this->language->get('error_gateway');
        }

        if (empty($this->request->post['external_transaction_id'])) {
            $this->error['external_transaction_id'] = $this->language->get('error_external_id');
        }

        if (empty($this->request->post['transaction_type'])) {
            $this->error['transaction_type'] = $this->language->get('error_transaction_type');
        }

        if (empty($this->request->post['amount']) || !is_numeric($this->request->post['amount']) || $this->request->post['amount'] <= 0) {
            $this->error['amount'] = $this->language->get('error_amount');
        }

        if (empty($this->request->post['transaction_date'])) {
            $this->error['transaction_date'] = $this->language->get('error_transaction_date');
        }

        return !$this->error;
    }

    protected function validateSettle() {
        if (!$this->user->hasPermission('modify', 'payment/transaction')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        if (empty($this->request->post['transaction_id'])) {
            $this->error['transaction_id'] = $this->language->get('error_transaction');
        }

        if (empty($this->request->post['settlement_date'])) {
            $this->error['settlement_date'] = $this->language->get('error_settlement_date');
        }

        return !$this->error;
    }

    protected function validateRefund() {
        if (!$this->user->hasPermission('modify', 'payment/transaction')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        if (empty($this->request->post['transaction_id'])) {
            $this->error['transaction_id'] = $this->language->get('error_transaction');
        }

        if (empty($this->request->post['refund_amount']) || !is_numeric($this->request->post['refund_amount']) || $this->request->post['refund_amount'] <= 0) {
            $this->error['refund_amount'] = $this->language->get('error_refund_amount');
        }

        if (empty($this->request->post['reason'])) {
            $this->error['reason'] = $this->language->get('error_refund_reason');
        }

        return !$this->error;
    }

    protected function validateDelete() {
        if (!$this->user->hasPermission('modify', 'payment/transaction')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        return !$this->error;
    }
}
