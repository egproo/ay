<?php
/**
 * AYM ERP System: Advanced Installment Payment Controller
 * 
 * نظام التقسيط المتقدم - مطور للشركات الحقيقية
 * 
 * الميزات المتقدمة:
 * - تكامل مع فاليو (Valu)
 * - تكامل مع باي تابس (PayTabs)
 * - تكامل مع باي موب (PayMob)
 * - إدارة خطط التقسيط المتعددة
 * - حساب الفوائد والرسوم تلقائياً
 * - تتبع الأقساط والمدفوعات
 * - إشعارات تلقائية للعملاء
 * - تكامل مع المحاسبة والضرائب
 * - دعم العملات المتعددة
 * - أمان متقدم وتشفير البيانات
 * 
 * @package    AYM ERP
 * @author     AYM Development Team
 * @copyright  2024 AYM ERP Systems
 * @license    Commercial License
 * @version    1.0.0
 * @since      2024-01-15
 */

class ControllerExtensionPaymentInstallment extends Controller {
    
    /**
     * الشاشة الرئيسية لإدارة التقسيط
     */
    public function index() {
        $this->load->language('extension/payment/installment');
        $this->document->setTitle($this->language->get('heading_title'));
        
        // التحقق من الصلاحيات
        if (!$this->user->hasPermission('access', 'extension/payment/installment')) {
            $this->session->data['error'] = $this->language->get('error_permission');
            $this->response->redirect($this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true));
        }
        
        // تحميل النماذج المطلوبة
        $this->load->model('extension/payment/installment');
        $this->load->model('sale/order');
        $this->load->model('customer/customer');
        
        // معالجة الفلاتر
        $filter_data = $this->getFilterData();
        
        // جلب خطط التقسيط
        $installment_plans = $this->model_extension_payment_installment->getInstallmentPlans($filter_data);
        $total_plans = $this->model_extension_payment_installment->getTotalInstallmentPlans($filter_data);
        
        // إعداد البيانات للعرض
        $data = $this->prepareViewData($installment_plans, $filter_data);
        
        // إعداد الترقيم
        $pagination = new Pagination();
        $pagination->total = $total_plans;
        $pagination->page = $filter_data['page'];
        $pagination->limit = $filter_data['limit'];
        $pagination->url = $this->url->link('extension/payment/installment', 'user_token=' . $this->session->data['user_token'] . $this->buildFilterUrl($filter_data) . '&page={page}', true);
        
        $data['pagination'] = $pagination->render();
        $data['results'] = sprintf($this->language->get('text_pagination'), ($total_plans) ? (($filter_data['page'] - 1) * $filter_data['limit']) + 1 : 0, ((($filter_data['page'] - 1) * $filter_data['limit']) > ($total_plans - $filter_data['limit'])) ? $total_plans : ((($filter_data['page'] - 1) * $filter_data['limit']) + $filter_data['limit']), $total_plans, ceil($total_plans / $filter_data['limit']));
        
        // تحميل القوالب
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');
        
        $this->response->setOutput($this->load->view('extension/payment/installment', $data));
    }
    
    /**
     * إنشاء خطة تقسيط جديدة
     */
    public function createInstallmentPlan() {
        $this->load->language('extension/payment/installment');
        $this->load->model('extension/payment/installment');
        
        $json = array('success' => false);
        
        if (isset($this->request->post['order_id']) && isset($this->request->post['provider'])) {
            $order_id = (int)$this->request->post['order_id'];
            $provider = $this->request->post['provider']; // valu, paytabs, paymob
            $plan_type = $this->request->post['plan_type']; // 3, 6, 9, 12, 18, 24 months
            $down_payment = isset($this->request->post['down_payment']) ? (float)$this->request->post['down_payment'] : 0;
            
            try {
                // التحقق من صحة الطلب
                $order_info = $this->model_sale_order->getOrder($order_id);
                
                if (!$order_info) {
                    $json['error'] = $this->language->get('error_order_not_found');
                } else {
                    // إنشاء خطة التقسيط
                    $plan_data = $this->model_extension_payment_installment->createInstallmentPlan($order_id, $provider, $plan_type, $down_payment);
                    
                    if ($plan_data) {
                        // إرسال للمزود المختار
                        $result = $this->sendToProvider($provider, $plan_data);
                        
                        if ($result['success']) {
                            // حفظ النتيجة في قاعدة البيانات
                            $this->model_extension_payment_installment->saveProviderResponse($plan_data['plan_id'], $result);
                            
                            $json['success'] = true;
                            $json['message'] = $this->language->get('text_plan_created_success');
                            $json['plan_id'] = $plan_data['plan_id'];
                            $json['payment_url'] = $result['payment_url'];
                        } else {
                            $json['error'] = $result['error'];
                        }
                    } else {
                        $json['error'] = $this->language->get('error_create_plan');
                    }
                }
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
     * معالجة دفع القسط
     */
    public function processInstallmentPayment() {
        $this->load->language('extension/payment/installment');
        $this->load->model('extension/payment/installment');
        
        $json = array('success' => false);
        
        if (isset($this->request->post['installment_id']) && isset($this->request->post['amount'])) {
            $installment_id = (int)$this->request->post['installment_id'];
            $amount = (float)$this->request->post['amount'];
            $payment_method = isset($this->request->post['payment_method']) ? $this->request->post['payment_method'] : 'cash';
            
            try {
                // التحقق من صحة القسط
                $installment_info = $this->model_extension_payment_installment->getInstallment($installment_id);
                
                if (!$installment_info) {
                    $json['error'] = $this->language->get('error_installment_not_found');
                } elseif ($installment_info['status'] == 'paid') {
                    $json['error'] = $this->language->get('error_installment_already_paid');
                } else {
                    // معالجة الدفع
                    $result = $this->model_extension_payment_installment->processPayment($installment_id, $amount, $payment_method);
                    
                    if ($result) {
                        // تحديث المحاسبة
                        $this->updateAccountingForPayment($installment_id, $amount);
                        
                        // إرسال إشعار للعميل
                        $this->sendPaymentNotification($installment_id);
                        
                        $json['success'] = true;
                        $json['message'] = $this->language->get('text_payment_processed_success');
                    } else {
                        $json['error'] = $this->language->get('error_process_payment');
                    }
                }
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
     * الحصول على خطط التقسيط المتاحة
     */
    public function getAvailablePlans() {
        $this->load->language('extension/payment/installment');
        $this->load->model('extension/payment/installment');
        
        $json = array();
        
        if (isset($this->request->get['order_total'])) {
            $order_total = (float)$this->request->get['order_total'];
            $customer_id = isset($this->request->get['customer_id']) ? (int)$this->request->get['customer_id'] : 0;
            
            // جلب خطط التقسيط المتاحة
            $available_plans = $this->model_extension_payment_installment->getAvailablePlans($order_total, $customer_id);
            
            $json['plans'] = array();
            
            foreach ($available_plans as $plan) {
                $json['plans'][] = array(
                    'provider' => $plan['provider'],
                    'provider_name' => $this->language->get('text_provider_' . $plan['provider']),
                    'months' => $plan['months'],
                    'monthly_payment' => $plan['monthly_payment'],
                    'total_amount' => $plan['total_amount'],
                    'interest_rate' => $plan['interest_rate'],
                    'fees' => $plan['fees'],
                    'min_down_payment' => $plan['min_down_payment'],
                    'max_down_payment' => $plan['max_down_payment']
                );
            }
        }
        
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
    
    /**
     * التحقق من أهلية العميل للتقسيط
     */
    public function checkCustomerEligibility() {
        $this->load->language('extension/payment/installment');
        $this->load->model('extension/payment/installment');
        $this->load->model('customer/customer');
        
        $json = array('eligible' => false);
        
        if (isset($this->request->get['customer_id'])) {
            $customer_id = (int)$this->request->get['customer_id'];
            $order_total = isset($this->request->get['order_total']) ? (float)$this->request->get['order_total'] : 0;
            
            // التحقق من أهلية العميل
            $eligibility = $this->model_extension_payment_installment->checkCustomerEligibility($customer_id, $order_total);
            
            $json['eligible'] = $eligibility['eligible'];
            $json['reasons'] = $eligibility['reasons'];
            $json['max_amount'] = $eligibility['max_amount'];
            $json['credit_score'] = $eligibility['credit_score'];
            $json['available_providers'] = $eligibility['available_providers'];
        }
        
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
    
    /**
     * الحصول على إحصائيات التقسيط
     */
    public function getInstallmentStatistics() {
        $this->load->language('extension/payment/installment');
        $this->load->model('extension/payment/installment');
        
        $json = array();
        
        $statistics = $this->model_extension_payment_installment->getInstallmentStatistics();
        
        $json['statistics'] = array(
            'total_plans' => $statistics['total_plans'] ?? 0,
            'active_plans' => $statistics['active_plans'] ?? 0,
            'completed_plans' => $statistics['completed_plans'] ?? 0,
            'overdue_installments' => $statistics['overdue_installments'] ?? 0,
            'total_amount_financed' => $statistics['total_amount_financed'] ?? 0,
            'total_collected' => $statistics['total_collected'] ?? 0,
            'collection_rate' => $statistics['collection_rate'] ?? 0,
            'average_plan_value' => $statistics['average_plan_value'] ?? 0
        );
        
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
    
    /**
     * إرسال البيانات للمزود المختار
     */
    private function sendToProvider($provider, $plan_data) {
        switch ($provider) {
            case 'valu':
                return $this->sendToValu($plan_data);
            case 'paytabs':
                return $this->sendToPayTabs($plan_data);
            case 'paymob':
                return $this->sendToPayMob($plan_data);
            default:
                return array('success' => false, 'error' => 'Unknown provider');
        }
    }
    
    /**
     * تكامل مع فاليو
     */
    private function sendToValu($plan_data) {
        $valu_config = array(
            'merchant_id' => $this->config->get('valu_merchant_id'),
            'secret_key' => $this->config->get('valu_secret_key'),
            'api_url' => $this->config->get('valu_environment') == 'production' ? 
                'https://api.valu.com.eg/v1/' : 'https://sandbox-api.valu.com.eg/v1/'
        );
        
        $request_data = array(
            'merchant_id' => $valu_config['merchant_id'],
            'order_id' => $plan_data['order_id'],
            'amount' => $plan_data['total_amount'],
            'currency' => 'EGP',
            'installments' => $plan_data['months'],
            'down_payment' => $plan_data['down_payment'],
            'customer' => array(
                'name' => $plan_data['customer_name'],
                'email' => $plan_data['customer_email'],
                'phone' => $plan_data['customer_phone'],
                'national_id' => $plan_data['customer_national_id']
            ),
            'callback_url' => $this->url->link('extension/payment/installment/callback', 'provider=valu', true),
            'return_url' => $this->url->link('extension/payment/installment/return', 'provider=valu', true)
        );
        
        // إضافة التوقيع
        $request_data['signature'] = $this->generateValuSignature($request_data, $valu_config['secret_key']);
        
        return $this->sendCurlRequest($valu_config['api_url'] . 'installments/create', $request_data);
    }
    
    /**
     * تكامل مع باي تابس
     */
    private function sendToPayTabs($plan_data) {
        $paytabs_config = array(
            'profile_id' => $this->config->get('paytabs_profile_id'),
            'server_key' => $this->config->get('paytabs_server_key'),
            'api_url' => $this->config->get('paytabs_environment') == 'production' ? 
                'https://secure.paytabs.com/' : 'https://secure-egypt.paytabs.com/'
        );
        
        $request_data = array(
            'profile_id' => $paytabs_config['profile_id'],
            'tran_type' => 'sale',
            'tran_class' => 'ecom',
            'cart_id' => $plan_data['order_id'],
            'cart_amount' => $plan_data['total_amount'],
            'cart_currency' => 'EGP',
            'cart_description' => 'Installment Plan for Order #' . $plan_data['order_id'],
            'paypage_lang' => 'ar',
            'customer_details' => array(
                'name' => $plan_data['customer_name'],
                'email' => $plan_data['customer_email'],
                'phone' => $plan_data['customer_phone'],
                'street1' => $plan_data['customer_address'],
                'city' => $plan_data['customer_city'],
                'state' => $plan_data['customer_state'],
                'country' => 'EG',
                'zip' => $plan_data['customer_postcode']
            ),
            'shipping_details' => array(
                'name' => $plan_data['customer_name'],
                'email' => $plan_data['customer_email'],
                'phone' => $plan_data['customer_phone'],
                'street1' => $plan_data['shipping_address'],
                'city' => $plan_data['shipping_city'],
                'state' => $plan_data['shipping_state'],
                'country' => 'EG',
                'zip' => $plan_data['shipping_postcode']
            ),
            'callback' => $this->url->link('extension/payment/installment/callback', 'provider=paytabs', true),
            'return' => $this->url->link('extension/payment/installment/return', 'provider=paytabs', true),
            'installments' => array(
                'enabled' => true,
                'months' => $plan_data['months'],
                'down_payment' => $plan_data['down_payment']
            )
        );
        
        $headers = array(
            'Authorization: ' . $paytabs_config['server_key'],
            'Content-Type: application/json'
        );
        
        return $this->sendCurlRequest($paytabs_config['api_url'] . 'payment/request', $request_data, $headers);
    }
    
    /**
     * تكامل مع باي موب
     */
    private function sendToPayMob($plan_data) {
        $paymob_config = array(
            'api_key' => $this->config->get('paymob_api_key'),
            'integration_id' => $this->config->get('paymob_integration_id'),
            'api_url' => 'https://accept.paymob.com/api/'
        );
        
        // الحصول على token
        $auth_token = $this->getPayMobAuthToken($paymob_config['api_key']);
        
        if (!$auth_token) {
            return array('success' => false, 'error' => 'Failed to get PayMob auth token');
        }
        
        // إنشاء الطلب
        $order_data = array(
            'auth_token' => $auth_token,
            'delivery_needed' => 'false',
            'amount_cents' => $plan_data['total_amount'] * 100,
            'currency' => 'EGP',
            'items' => array()
        );
        
        $order_response = $this->sendCurlRequest($paymob_config['api_url'] . 'ecommerce/orders', $order_data);
        
        if (!$order_response['success']) {
            return $order_response;
        }
        
        // إنشاء payment key
        $payment_data = array(
            'auth_token' => $auth_token,
            'amount_cents' => $plan_data['total_amount'] * 100,
            'expiration' => 3600,
            'order_id' => $order_response['data']['id'],
            'billing_data' => array(
                'apartment' => 'NA',
                'email' => $plan_data['customer_email'],
                'floor' => 'NA',
                'first_name' => $plan_data['customer_name'],
                'street' => $plan_data['customer_address'],
                'building' => 'NA',
                'phone_number' => $plan_data['customer_phone'],
                'shipping_method' => 'NA',
                'postal_code' => $plan_data['customer_postcode'],
                'city' => $plan_data['customer_city'],
                'country' => 'EG',
                'last_name' => '',
                'state' => $plan_data['customer_state']
            ),
            'currency' => 'EGP',
            'integration_id' => $paymob_config['integration_id'],
            'installments' => array(
                'enabled' => true,
                'months' => $plan_data['months'],
                'down_payment' => $plan_data['down_payment']
            )
        );
        
        return $this->sendCurlRequest($paymob_config['api_url'] . 'acceptance/payment_keys', $payment_data);
    }
    
    /**
     * وظائف مساعدة
     */
    
    private function sendCurlRequest($url, $data, $headers = array()) {
        $ch = curl_init();
        
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        
        if (empty($headers)) {
            $headers = array('Content-Type: application/json');
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            return array('success' => false, 'error' => 'cURL Error: ' . $error);
        }
        
        $response_data = json_decode($response, true);
        
        if ($http_code >= 200 && $http_code < 300) {
            return array('success' => true, 'data' => $response_data);
        } else {
            $error_message = isset($response_data['error']) ? $response_data['error'] : 'HTTP Error: ' . $http_code;
            return array('success' => false, 'error' => $error_message, 'http_code' => $http_code);
        }
    }
    
    private function generateValuSignature($data, $secret_key) {
        ksort($data);
        $string_to_sign = '';
        foreach ($data as $key => $value) {
            if ($key != 'signature') {
                $string_to_sign .= $key . '=' . $value . '&';
            }
        }
        $string_to_sign = rtrim($string_to_sign, '&');
        return hash_hmac('sha256', $string_to_sign, $secret_key);
    }
    
    private function getPayMobAuthToken($api_key) {
        $auth_data = array('api_key' => $api_key);
        $response = $this->sendCurlRequest('https://accept.paymob.com/api/auth/tokens', $auth_data);
        
        return $response['success'] ? $response['data']['token'] : false;
    }
    
    private function updateAccountingForPayment($installment_id, $amount) {
        // تحديث القيود المحاسبية للقسط المدفوع
        $this->load->model('accounting/journal');
        
        // إنشاء قيد محاسبي للدفع
        $journal_data = array(
            'description' => 'Installment Payment #' . $installment_id,
            'amount' => $amount,
            'type' => 'installment_payment',
            'reference_id' => $installment_id
        );
        
        $this->model_accounting_journal->addJournalEntry($journal_data);
    }
    
    private function sendPaymentNotification($installment_id) {
        // إرسال إشعار للعميل بدفع القسط
        $this->load->model('mail/mail');
        
        $installment_info = $this->model_extension_payment_installment->getInstallment($installment_id);
        
        if ($installment_info && $installment_info['customer_email']) {
            $subject = $this->language->get('text_payment_notification_subject');
            $message = sprintf($this->language->get('text_payment_notification_message'), 
                $installment_info['customer_name'], 
                $installment_info['amount'],
                $installment_info['due_date']
            );
            
            $this->model_mail_mail->send($installment_info['customer_email'], $subject, $message);
        }
    }
    
    private function getFilterData() {
        $filter_data = array();
        
        $filter_data['filter_customer'] = isset($this->request->get['filter_customer']) ? $this->request->get['filter_customer'] : '';
        $filter_data['filter_provider'] = isset($this->request->get['filter_provider']) ? $this->request->get['filter_provider'] : '';
        $filter_data['filter_status'] = isset($this->request->get['filter_status']) ? $this->request->get['filter_status'] : '';
        $filter_data['filter_date_from'] = isset($this->request->get['filter_date_from']) ? $this->request->get['filter_date_from'] : '';
        $filter_data['filter_date_to'] = isset($this->request->get['filter_date_to']) ? $this->request->get['filter_date_to'] : '';
        
        $filter_data['sort'] = isset($this->request->get['sort']) ? $this->request->get['sort'] : 'ip.date_created';
        $filter_data['order'] = isset($this->request->get['order']) ? $this->request->get['order'] : 'DESC';
        $filter_data['page'] = isset($this->request->get['page']) ? (int)$this->request->get['page'] : 1;
        $filter_data['limit'] = $this->config->get('config_limit_admin');
        $filter_data['start'] = ($filter_data['page'] - 1) * $filter_data['limit'];
        
        return $filter_data;
    }
    
    private function prepareViewData($installment_plans, $filter_data) {
        $data = array();
        
        // معلومات أساسية
        $data['heading_title'] = $this->language->get('heading_title');
        $data['text_list'] = $this->language->get('text_list');
        
        // الفلاتر
        $data['filter_customer'] = $filter_data['filter_customer'];
        $data['filter_provider'] = $filter_data['filter_provider'];
        $data['filter_status'] = $filter_data['filter_status'];
        $data['filter_date_from'] = $filter_data['filter_date_from'];
        $data['filter_date_to'] = $filter_data['filter_date_to'];
        
        // خطط التقسيط
        $data['installment_plans'] = array();
        foreach ($installment_plans as $plan) {
            $data['installment_plans'][] = array(
                'plan_id' => $plan['plan_id'],
                'order_id' => $plan['order_id'],
                'customer' => $plan['customer_name'],
                'provider' => $plan['provider'],
                'total_amount' => $this->currency->format($plan['total_amount'], $plan['currency_code']),
                'monthly_payment' => $this->currency->format($plan['monthly_payment'], $plan['currency_code']),
                'months' => $plan['months'],
                'status' => $plan['status'],
                'date_created' => date($this->language->get('date_format_short'), strtotime($plan['date_created']))
            );
        }
        
        // الروابط
        $data['user_token'] = $this->session->data['user_token'];
        
        return $data;
    }
    
    private function buildFilterUrl($filter_data) {
        $url = '';
        
        if ($filter_data['filter_customer']) {
            $url .= '&filter_customer=' . urlencode($filter_data['filter_customer']);
        }
        
        if ($filter_data['filter_provider']) {
            $url .= '&filter_provider=' . urlencode($filter_data['filter_provider']);
        }
        
        if ($filter_data['filter_status']) {
            $url .= '&filter_status=' . urlencode($filter_data['filter_status']);
        }
        
        if ($filter_data['filter_date_from']) {
            $url .= '&filter_date_from=' . urlencode($filter_data['filter_date_from']);
        }
        
        if ($filter_data['filter_date_to']) {
            $url .= '&filter_date_to=' . urlencode($filter_data['filter_date_to']);
        }
        
        return $url;
    }
}
