<?php
/**
 * نموذج إدارة الاشتراكات
 * 
 * @package     OpenCart ERP+eCommerce
 * @author      Development Team
 * @copyright   Copyright (c) 2023-2025
 */
class ModelSubscriptionSubscription extends Model {
    /**
     * الحصول على معلومات الاشتراك الحالي
     * 
     * @return array
     */
    public function getCurrentSubscription() {
        try {
            // جلب بيانات الاشتراك من واجهة برمجة التطبيقات
            $subscription_data = $this->makeApiRequest('GET', 'subscription/current');
            
            if (!$subscription_data || !isset($subscription_data['subscription'])) {
                return [];
            }
            
            return $this->processSubscriptionData($subscription_data['subscription']);
        } catch (Exception $e) {
            $this->log->write('Error fetching current subscription: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * معالجة بيانات الاشتراك
     * 
     * @param array $subscription_data
     * @return array
     */
    private function processSubscriptionData($subscription_data) {
        // تنسيق البيانات للعرض
        if (isset($subscription_data['status'])) {
            $subscription_data['status_formatted'] = $this->formatSubscriptionStatus($subscription_data['status']);
        }
        
        // تنسيق تواريخ البداية والانتهاء
        if (isset($subscription_data['start_date'])) {
            $subscription_data['start_date_formatted'] = date($this->language->get('date_format_short'), strtotime($subscription_data['start_date']));
        }
        
        if (isset($subscription_data['expiry_date'])) {
            $subscription_data['expiry_date_formatted'] = date($this->language->get('date_format_short'), strtotime($subscription_data['expiry_date']));
            
            // حساب عدد الأيام المتبقية
            $now = time();
            $expiry = strtotime($subscription_data['expiry_date']);
            $subscription_data['days_left'] = max(0, floor(($expiry - $now) / (60 * 60 * 24)));
        }
        
        return $subscription_data;
    }
    
    /**
     * تنسيق حالة الاشتراك
     * 
     * @param string $status
     * @return string
     */
    private function formatSubscriptionStatus($status) {
        $status_map = [
            'active' => $this->language->get('text_status_active'),
            'pending' => $this->language->get('text_status_pending'),
            'expired' => $this->language->get('text_status_expired'),
            'cancelled' => $this->language->get('text_status_cancelled'),
            'suspended' => $this->language->get('text_status_suspended')
        ];
        
        return isset($status_map[$status]) ? $status_map[$status] : $status;
    }
    
    /**
     * الحصول على خطط الاشتراك المتاحة
     * 
     * @return array
     */
    public function getAvailablePlans() {
        try {
            // جلب بيانات الخطط من واجهة برمجة التطبيقات
            $plans_data = $this->makeApiRequest('GET', 'subscription/plans');
            
            if (!$plans_data || !isset($plans_data['plans'])) {
                return [];
            }
            
            // معالجة البيانات وتنسيقها
            $plans = [];
            foreach ($plans_data['plans'] as $plan) {
                $plan['price_formatted'] = $this->currency->format($plan['price_monthly'], $this->config->get('config_currency'));
                $plan['price_annual_formatted'] = $this->currency->format($plan['price_annually'], $this->config->get('config_currency'));
                
                // تحويل قائمة الميزات إلى مصفوفة منظمة
                if (isset($plan['features']) && is_string($plan['features'])) {
                    $plan['features_array'] = explode(',', $plan['features']);
                } elseif (!isset($plan['features_array'])) {
                    $plan['features_array'] = [];
                }
                
                // إضافة صورة أو رمز للخطة
                $plan['icon'] = $plan['icon'] ?? 'fa-star';
                
                $plans[] = $plan;
            }
            
            return $plans;
        } catch (Exception $e) {
            $this->log->write('Error fetching available plans: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * الحصول على إحصائيات الاستخدام
     * 
     * @param bool $force_refresh إجبار تحديث البيانات
     * @return array
     */
    public function getUsageStatistics($force_refresh = false) {
        try {
            // جلب بيانات الاستخدام من واجهة برمجة التطبيقات
            $url = 'subscription/usage';
            if ($force_refresh) {
                $url .= '?refresh=1';
            }
            
            $usage_data = $this->makeApiRequest('GET', $url);
            
            if (!$usage_data || !isset($usage_data['usage'])) {
                return [];
            }
            
            // تنسيق البيانات للعرض
            $usage = [];
            
            // بيانات مساحة التخزين
            if (isset($usage_data['usage']['storage'])) {
                $storage_info = $usage_data['usage']['storage'];
                $storage_limit_mb = $storage_info['limit'] / (1024 * 1024); // تحويل البايت إلى ميجابايت
                $storage_used_mb = $storage_info['used'] / (1024 * 1024);
                
                $usage['storage'] = [
                    'used' => $storage_used_mb,
                    'limit' => $storage_limit_mb,
                    'unit' => 'MB',
                    'used_formatted' => number_format($storage_used_mb, 2) . ' MB',
                    'limit_formatted' => number_format($storage_limit_mb, 2) . ' MB',
                ];
            }
            
            // بيانات حركة البيانات
            if (isset($usage_data['usage']['traffic'])) {
                $traffic_info = $usage_data['usage']['traffic'];
                $traffic_limit_gb = $traffic_info['limit'] / (1024 * 1024 * 1024); // تحويل البايت إلى جيجابايت
                $traffic_used_gb = $traffic_info['used'] / (1024 * 1024 * 1024);
                
                $usage['traffic'] = [
                    'used' => $traffic_used_gb,
                    'limit' => $traffic_limit_gb,
                    'unit' => 'GB',
                    'used_formatted' => number_format($traffic_used_gb, 2) . ' GB',
                    'limit_formatted' => number_format($traffic_limit_gb, 2) . ' GB',
                ];
            }
            
            // بيانات الطلبات
            if (isset($usage_data['usage']['orders'])) {
                $orders_info = $usage_data['usage']['orders'];
                
                $usage['orders'] = [
                    'used' => $orders_info['used'],
                    'limit' => $orders_info['limit'],
                    'unit' => '',
                    'used_formatted' => number_format($orders_info['used']),
                    'limit_formatted' => number_format($orders_info['limit']),
                ];
            }
            
            // بيانات المنتجات
            if (isset($usage_data['usage']['products'])) {
                $products_info = $usage_data['usage']['products'];
                
                $usage['products'] = [
                    'used' => $products_info['used'],
                    'limit' => $products_info['limit'],
                    'unit' => '',
                    'used_formatted' => number_format($products_info['used']),
                    'limit_formatted' => number_format($products_info['limit']),
                ];
            }
            
            return $usage;
        } catch (Exception $e) {
            $this->log->write('Error fetching usage statistics: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * الحصول على سجل الاشتراكات
     * 
     * @return array
     */
    public function getSubscriptionHistory() {
        try {
            // جلب بيانات سجل الاشتراكات من واجهة برمجة التطبيقات
            $history_data = $this->makeApiRequest('GET', 'subscription/history');
            
            if (!$history_data || !isset($history_data['history'])) {
                return [];
            }
            
            // تنسيق البيانات للعرض
            $history = [];
            foreach ($history_data['history'] as $item) {
                // تنسيق التواريخ
                $item['date_formatted'] = date($this->language->get('date_format_short'), strtotime($item['date']));
                
                // تنسيق الأسعار
                if (isset($item['price'])) {
                    $item['price_formatted'] = $this->currency->format($item['price'], $this->config->get('config_currency'));
                }
                
                // تنسيق الحالة
                if (isset($item['status'])) {
                    $item['status_formatted'] = $this->formatSubscriptionStatus($item['status']);
                }
                
                $history[] = $item;
            }
            
            return $history;
        } catch (Exception $e) {
            $this->log->write('Error fetching subscription history: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * الحصول على قائمة الفواتير
     * 
     * @return array
     */
    public function getInvoices() {
        try {
            // جلب بيانات الفواتير من واجهة برمجة التطبيقات
            $invoices_data = $this->makeApiRequest('GET', 'subscription/invoices');
            
            if (!$invoices_data || !isset($invoices_data['invoices'])) {
                return [];
            }
            
            // تنسيق البيانات للعرض
            $invoices = [];
            foreach ($invoices_data['invoices'] as $invoice) {
                // تنسيق التواريخ
                $invoice['date_formatted'] = date($this->language->get('date_format_short'), strtotime($invoice['date']));
                
                // تنسيق الأسعار
                if (isset($invoice['amount'])) {
                    $invoice['amount_formatted'] = $this->currency->format($invoice['amount'], $this->config->get('config_currency'));
                }
                
                // تنسيق الحالة
                if (isset($invoice['status'])) {
                    $invoice['status_formatted'] = $this->formatInvoiceStatus($invoice['status']);
                }
                
                $invoices[] = $invoice;
            }
            
            return $invoices;
        } catch (Exception $e) {
            $this->log->write('Error fetching invoices: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * تنسيق حالة الفاتورة
     * 
     * @param string $status
     * @return string
     */
    private function formatInvoiceStatus($status) {
        $status_map = [
            'paid' => $this->language->get('text_status_paid'),
            'unpaid' => $this->language->get('text_status_unpaid'),
            'cancelled' => $this->language->get('text_status_cancelled'),
            'pending' => $this->language->get('text_status_pending')
        ];
        
        return isset($status_map[$status]) ? $status_map[$status] : $status;
    }
    
    /**
     * الحصول على تفاصيل فاتورة
     * 
     * @param int $invoice_id
     * @return array
     */
    public function getInvoiceDetails($invoice_id) {
        try {
            // جلب تفاصيل الفاتورة من واجهة برمجة التطبيقات
            $invoice_data = $this->makeApiRequest('GET', 'subscription/invoice/' . $invoice_id);
            
            if (!$invoice_data || !isset($invoice_data['invoice'])) {
                return [];
            }
            
            $invoice = $invoice_data['invoice'];
            
            // تنسيق التواريخ
            $invoice['date_formatted'] = date($this->language->get('date_format_short'), strtotime($invoice['date']));
            
            // تنسيق الأسعار
            if (isset($invoice['amount'])) {
                $invoice['amount_formatted'] = $this->currency->format($invoice['amount'], $this->config->get('config_currency'));
            }
            
            if (isset($invoice['subtotal'])) {
                $invoice['subtotal_formatted'] = $this->currency->format($invoice['subtotal'], $this->config->get('config_currency'));
            }
            
            if (isset($invoice['tax_amount'])) {
                $invoice['tax_formatted'] = $this->currency->format($invoice['tax_amount'], $this->config->get('config_currency'));
            }
            
            if (isset($invoice['discount_amount'])) {
                $invoice['discount_formatted'] = $this->currency->format($invoice['discount_amount'], $this->config->get('config_currency'));
            }
            
            // تنسيق الحالة
            if (isset($invoice['status'])) {
                $invoice['status_formatted'] = $this->formatInvoiceStatus($invoice['status']);
            }
            
            // تنسيق العناصر
            if (isset($invoice['items']) && is_array($invoice['items'])) {
                foreach ($invoice['items'] as &$item) {
                    if (isset($item['price'])) {
                        $item['price_formatted'] = $this->currency->format($item['price'], $this->config->get('config_currency'));
                    }
                    
                    if (isset($item['total'])) {
                        $item['total_formatted'] = $this->currency->format($item['total'], $this->config->get('config_currency'));
                    }
                }
            }
            
            return $invoice;
        } catch (Exception $e) {
            $this->log->write('Error fetching invoice details: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * الحصول على ملف PDF للفاتورة
     * 
     * @param int $invoice_id
     * @return string|bool مسار الملف أو false في حالة الفشل
     */
    public function getInvoicePdf($invoice_id) {
        try {
            // جلب ملف PDF للفاتورة من واجهة برمجة التطبيقات
            $pdf_data = $this->makeApiRequest('GET', 'subscription/invoice/' . $invoice_id . '/pdf', true);
            
            if (!$pdf_data) {
                return false;
            }
            
            // حفظ الملف مؤقتًا
            $temp_file = DIR_DOWNLOAD . 'invoice_' . $invoice_id . '_' . time() . '.pdf';
            file_put_contents($temp_file, $pdf_data);
            
            return $temp_file;
        } catch (Exception $e) {
            $this->log->write('Error fetching invoice PDF: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * ترقية الاشتراك
     * 
     * @param int $plan_id معرف الخطة
     * @param string $payment_method طريقة الدفع
     * @return array
     */
    public function upgradeSubscription($plan_id, $payment_method) {
        try {
            // إرسال طلب الترقية إلى واجهة برمجة التطبيقات
            $data = [
                'plan_id' => $plan_id,
                'payment_method' => $payment_method
            ];
            
            $response = $this->makeApiRequest('POST', 'subscription/upgrade', false, $data);
            
            if (!$response) {
                return [
                    'success' => false,
                    'message' => $this->language->get('error_api_connection')
                ];
            }
            
            if (isset($response['error'])) {
                return [
                    'success' => false,
                    'message' => $response['error']
                ];
            }
            
            return [
                'success' => true,
                'payment_url' => $response['payment_url'] ?? null,
                'message' => $response['message'] ?? $this->language->get('text_upgrade_success')
            ];
        } catch (Exception $e) {
            $this->log->write('Error upgrading subscription: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * تجديد الاشتراك
     * 
     * @param string $payment_method طريقة الدفع
     * @return array
     */
    public function renewSubscription($payment_method) {
        try {
            // إرسال طلب التجديد إلى واجهة برمجة التطبيقات
            $data = [
                'payment_method' => $payment_method
            ];
            
            $response = $this->makeApiRequest('POST', 'subscription/renew', false, $data);
            
            if (!$response) {
                return [
                    'success' => false,
                    'message' => $this->language->get('error_api_connection')
                ];
            }
            
            if (isset($response['error'])) {
                return [
                    'success' => false,
                    'message' => $response['error']
                ];
            }
            
            return [
                'success' => true,
                'payment_url' => $response['payment_url'] ?? null,
                'message' => $response['message'] ?? $this->language->get('text_renew_success')
            ];
        } catch (Exception $e) {
            $this->log->write('Error renewing subscription: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * معالجة دفع فاتورة
     * 
     * @param int $invoice_id معرف الفاتورة
     * @return array
     */
    public function processInvoicePayment($invoice_id) {
        try {
            // إرسال طلب الدفع إلى واجهة برمجة التطبيقات
            $response = $this->makeApiRequest('POST', 'subscription/invoice/' . $invoice_id . '/pay');
            
            if (!$response) {
                return [
                    'success' => false,
                    'message' => $this->language->get('error_api_connection')
                ];
            }
            
            if (isset($response['error'])) {
                return [
                    'success' => false,
                    'message' => $response['error']
                ];
            }
            
            return [
                'success' => true,
                'payment_url' => $response['payment_url'] ?? null,
                'message' => $response['message'] ?? $this->language->get('text_payment_success')
            ];
        } catch (Exception $e) {
            $this->log->write('Error processing invoice payment: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * الحصول على طرق الدفع المتاحة
     * 
     * @return array
     */
    public function getPaymentMethods() {
        try {
            // جلب طرق الدفع المتاحة من واجهة برمجة التطبيقات
            $methods_data = $this->makeApiRequest('GET', 'subscription/payment-methods');
            
            if (!$methods_data || !isset($methods_data['methods'])) {
                return [];
            }
            
            // معالجة البيانات وتنسيقها
            $methods = [];
            foreach ($methods_data['methods'] as $method) {
                // إضافة صورة أو رمز لطريقة الدفع
                $method['icon'] = $method['icon'] ?? 'fa-credit-card';
                
                $methods[] = $method;
            }
            
            return $methods;
        } catch (Exception $e) {
            $this->log->write('Error fetching payment methods: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * الحصول على معلومات الدعم الفني
     * 
     * @return array
     */
    public function getSupportInfo() {
        try {
            // جلب معلومات الدعم الفني من واجهة برمجة التطبيقات
            $support_data = $this->makeApiRequest('GET', 'subscription/support-info');
            
            if (!$support_data || !isset($support_data['support'])) {
                return [];
            }
            
            return $support_data['support'];
        } catch (Exception $e) {
            $this->log->write('Error fetching support info: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * إرسال رسالة إلى الدعم الفني
     * 
     * @param string $subject الموضوع
     * @param string $message الرسالة
     * @return array
     */
    public function sendSupportMessage($subject, $message) {
        try {
            // إرسال الرسالة إلى واجهة برمجة التطبيقات
            $data = [
                'subject' => $subject,
                'message' => $message
            ];
            
            $response = $this->makeApiRequest('POST', 'subscription/support', false, $data);
            
            if (!$response) {
                return [
                    'success' => false,
                    'message' => $this->language->get('error_api_connection')
                ];
            }
            
            if (isset($response['error'])) {
                return [
                    'success' => false,
                    'message' => $response['error']
                ];
            }
            
            return [
                'success' => true,
                'ticket_id' => $response['ticket_id'] ?? null,
                'message' => $response['message'] ?? $this->language->get('text_message_sent')
            ];
        } catch (Exception $e) {
            $this->log->write('Error sending support message: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * إجراء طلب لواجهة برمجة التطبيقات
     * 
     * @param string $method طريقة الطلب
     * @param string $endpoint نقطة النهاية
     * @param bool $raw_response إرجاع الاستجابة الخام
     * @param array $data البيانات المرسلة
     * @return mixed
     * @throws Exception
     */
    private function makeApiRequest($method, $endpoint, $raw_response = false, $data = []) {
        // تكوين عنوان واجهة برمجة التطبيقات
        $api_url = $this->config->get('subscription_api_url');
        
        if (!$api_url) {
            $api_url = 'https://api.erp-ecommerce.com/api/v1/';
        }
        
        // إضافة مفتاح API
        $api_key = $this->config->get('subscription_api_key');
        
        if (!$api_key) {
            throw new Exception($this->language->get('error_api_key_missing'));
        }
        
        // تكوين رأس الطلب
        $headers = [
            'Authorization: Bearer ' . $api_key,
            'Content-Type: application/json',
            'Accept: application/json'
        ];
        
        // استخدام Axios عبر curl
        $ch = curl_init($api_url . $endpoint);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        
        if ($method === 'POST' && !empty($data)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        
        curl_close($ch);
        
        if ($error) {
            throw new Exception('cURL Error: ' . $error);
        }
        
        if ($http_code >= 400) {
            $error_data = json_decode($response, true);
            $error_message = isset($error_data['message']) ? $error_data['message'] : 'API Error: HTTP ' . $http_code;
            throw new Exception($error_message);
        }
        
        if ($raw_response) {
            return $response;
        }
        
        $data = json_decode($response, true);
        
        if (!$data) {
            throw new Exception('Invalid API response format');
        }
        
        return $data;
    }
}