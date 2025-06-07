<?php
class ControllerCheckoutQuickCheckout extends Controller {
    private $error = array();

    public function getInitialData() {
        $this->load->language('checkout/checkout');
        $this->load->model('checkout/order');
        $this->load->model('account/customer');
        $this->load->model('account/address');
        $this->load->model('localisation/zone');
        $this->load->model('localisation/country');
        $this->load->model('setting/extension');
        $this->load->model('account/customer_group');
        $this->load->model('extension/total/coupon');

        $json = array();

        $default_address = [
            'country_id' => 63,
            'zone_id' => 1011,
            'city' => '',
            'address_1' => '',
            'address_2' => '',
            'postcode' => '',
        ];

        if ($this->customer->isLogged()) {
            $json['logged'] = true;
            $customer_id = $this->customer->getId();
            $json['customer_id'] = $customer_id;
            $customer_info = $this->model_account_customer->getCustomer($customer_id);
            $json['customer_info'] = $customer_info;
            $addresses = $this->model_account_address->getAddresses();
            $json['addresses'] = $addresses;

            $this->session->data['quick_checkout'] = [
                'name' => $customer_info['firstname'] . ' ' . $customer_info['lastname'],
                'phone' => $customer_info['telephone'],
                'email' => $customer_info['email'],
                'customer_group' => $customer_info['customer_group_id']
            ];

            $default_address = $this->model_account_address->getAddress($this->customer->getAddressId());
            if ($default_address) {
                $this->session->data['quick_checkout'] += [
                    'address_1' => $default_address['address_1'],
                    'address_2' => $default_address['address_2'],
                    'city' => $default_address['city'],
                    'zone_id' => $default_address['zone_id'],
                    'country_id' => $default_address['country_id']
                ];
            }
        } else {
            $json['logged'] = false;
            $json['customer_info'] = array();
            $json['addresses'] = array();

            $this->session->data['quick_checkout'] = [
                'name' => '',
                'phone' => '',
                'email' => '',
                'customer_group' => 1,
                'address_1' => $default_address['address_1'],
                'address_2' => $default_address['address_2'],
                'city' => $default_address['city'],
                'zone_id' => $default_address['zone_id'],
                'country_id' => $default_address['country_id']
            ];
        }
        $this->load->language('checkout/checkout');

        $json['cart_data'] = $this->getCartData();
        $json['zones'] = $this->model_localisation_zone->getZonesByCountryId(63);
        $json['payment_methods'] = $this->getPaymentMethods();
        $json['shipping_methods'] = $this->getShippingMethods();
        $json['customer_groups'] = $this->model_account_customer_group->getCustomerGroups();

        $calculated = $this->calculateTotals();
        $this->session->data['quick_checkout']['total'] = $calculated['total'];
        $this->session->data['quick_checkout']['totals'] = $calculated['totals'];
        $json['total_raw'] = $calculated['total'];
        $country_info = $this->model_localisation_country->getCountry($default_address['country_id']);
        $json['country'] = $country_info ? $country_info['name'] : '';
        $json['country_id'] = $country_info ? $country_info['country_id'] : $default_address['country_id'];

        $zone_id = $this->session->data['quick_checkout']['zone_id'] ?? $default_address['zone_id'];
        $zone_info = $this->model_localisation_zone->getZone($zone_id);
        $json['zone'] = $zone_info ? $zone_info['name'] : '';
        $json['zone_id'] = $zone_info ? $zone_info['zone_id'] : $default_address['zone_id'];

        if (isset($this->session->data['quick_checkout']['coupon'])) {
            $this->session->data['coupon'] = $this->session->data['quick_checkout']['coupon'];
        }
        
        if (isset($this->session->data['coupon'])) {
            $coupon_info = $this->model_extension_total_coupon->getCoupon($this->session->data['coupon']);
            if (!$coupon_info) {
                $this->error['coupon'] = $this->language->get('error_coupon');
                unset($this->session->data['coupon']);
            }
        }

        if (!isset($this->session->data['quick_checkout']['payment_method_code']) && !empty($json['payment_methods'])) {
            $this->session->data['quick_checkout']['payment_method_code'] = $json['payment_methods'][0]['code'];
        }
        if (!isset($this->session->data['quick_checkout']['shipping_method_code']) && !empty($json['shipping_methods'])) {
            $this->session->data['quick_checkout']['shipping_method_code'] = $json['shipping_methods'][0]['code'];
        }

        $this->session->data['shipping_address'] = [
            'country_id' => $this->session->data['quick_checkout']['country_id'],
            'zone_id' => $this->session->data['quick_checkout']['zone_id'],
            'city' => $this->session->data['quick_checkout']['city'],
            'address_1' => $this->session->data['quick_checkout']['address_1'],
            'address_2' => $this->session->data['quick_checkout']['address_2'],
            'postcode' => $default_address['postcode'],
        ];
        $this->session->data['payment_address'] = [
            'country_id' => $this->session->data['quick_checkout']['country_id'],
            'zone_id' => $this->session->data['quick_checkout']['zone_id'],
            'city' => $this->session->data['quick_checkout']['city'],
            'address_1' => $this->session->data['quick_checkout']['address_1'],
            'address_2' => $this->session->data['quick_checkout']['address_2'],
            'postcode' => $default_address['postcode'],
        ];

        if (isset($this->session->data['quick_checkout']['shipping_method_code'])) {
            foreach ($json['shipping_methods'] as $shipping_method) {
                if ($shipping_method['code'] === $this->session->data['quick_checkout']['shipping_method_code']) {
                    $this->session->data['shipping_method'] = [
                        'code' => $shipping_method['code'],
                        'title' => $shipping_method['title'],
                        'cost' => $shipping_method['cost'],
                        'tax_class_id' => $shipping_method['tax_class_id'] ?? 0
                    ];
                    break;
                }
            }
        }

        $json['session_data'] = $this->session->data['quick_checkout'];
        $json['all_session_data'] = $this->session->data;
        $json['errors'] = $this->error;

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

public function updateSessionAndValidate() {
    $this->load->language('checkout/checkout');
    $this->load->model('account/customer');
    $this->load->model('account/address');
    $this->load->model('localisation/zone');
    $this->load->model('localisation/country');
    $this->load->model('setting/extension');
    $this->load->model('extension/total/coupon');

    $json = array();
    $this->error = array();

    // تحقق من الـ CSRF Token
    if (!isset($this->session->data['csrf_token']) || 
        !isset($this->request->post['csrf_token']) || 
        ($this->session->data['csrf_token'] !== $this->request->post['csrf_token'])) {
        $json['error'] = $this->language->get('error_csrf');
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
        return;
    }

    // 1) تحديث بيانات الجلسة (الاسم، الهاتف...إلخ)
    $data = $this->request->post;
    $this->updateSessionData($data);

    // 2) التحقق من صحة البيانات
    $this->validateData($data);

    // 3) التعامل مع الكوبون
    $this->handleCoupon($data);

    // 4) اجلب وسائل الشحن والدفع
    $shipping_methods = $this->getShippingMethods();
    $payment_methods = $this->getPaymentMethods();

    // **هنا لب المشكلة**: إذا اختار المستخدم وسيلة شحن، ضَع تفاصيلها في جلسة الطلب
    if (!empty($data['shipping_method_code'])) {
        foreach ($shipping_methods as $method) {
            if ($method['code'] === $data['shipping_method_code']) {
                $this->session->data['shipping_method'] = [
                    'code'        => $method['code'],
                    'title'       => $method['title'],
                    'cost'        => $method['cost'],
                    'tax_class_id'=> isset($method['tax_class_id']) ? $method['tax_class_id'] : 0,
                    'text'        => isset($method['text']) ? $method['text'] : ''
                ];
                break;
            }
        }
    }

    // 5) احسب الإجماليات
    $totals = $this->calculateTotals();

    // كوّن ردّ الـ JSON
    $json['cart_data'] = array(
        'totals' => isset($totals['totals']) ? $totals['totals'] : [],
        'total'  => isset($totals['total'])  
                     ? $this->currency->format($totals['total'], $this->session->data['currency']) 
                     : '0'
    );

    // سنخزِّن إجمالي الطلب وتفاصيله في quick_checkout أيضًا (لو أنت تستخدمه)
    $this->session->data['quick_checkout']['total']  = $totals['total'];
    $this->session->data['quick_checkout']['totals'] = $totals['totals'];
    $json['total_raw'] = $totals['total'];

    // بقية البيانات المرجعة
    $json['errors'] = $this->error;
    $json['logged'] = $this->customer->isLogged();
    $json['shipping_methods'] = $shipping_methods;
    $json['payment_methods']  = $payment_methods;
    $json['coupon_applied']   = isset($this->session->data['coupon']);
    $json['coupon_message']   = isset($this->error['coupon']) ? $this->error['coupon'] : '';
    $json['session_data']     = $this->session->data['quick_checkout'];

    // إرسال الاستجابة
    $this->response->addHeader('Content-Type: application/json');
    $this->response->setOutput(json_encode($json));
}

    public function submitOrder() {
        $this->load->language('checkout/checkout');
        $json = [];

        if ($this->request->server['REQUEST_METHOD'] == 'POST') {
            if ($this->validateOrder()) {
                $order_data = $this->getOrderData();
                $this->load->model('checkout/order');

                $order_id = $this->model_checkout_order->addOrder($order_data);
                $this->session->data['order_id'] = $order_id;

                $json['success'] = true;
                $json['order_id'] = $order_id;
                $json['paymentUrl'] = $this->url->link('extension/payment/' . $this->session->data['quick_checkout']['payment_method_code'] . '/confirm', '', true);
            } else {
                $json['error'] = $this->error;
            }
        } else {
            $json['error'] = 'Invalid method';
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    private function updateSessionData($data) {
        $fields = ['name', 'phone', 'email', 'customer_group', 'rin_customer', 'zone_id', 'city', 'address_1', 'address_2', 'payment_method_code', 'shipping_method_code', 'comment', 'coupon'];
        
        foreach ($fields as $field) {
            if (isset($data[$field])) {
                $this->session->data['quick_checkout'][$field] = $this->db->escape(strip_tags($data[$field]));
            }
        }
    }

    private function validateData($data) {
        if (!isset($data['name']) || !$data['name']) {
            $this->error['name'] = 'أدخل الاسم بشكل صحيح';
        } elseif (count(explode(' ', trim($data['name']))) < 2) {
            $this->error['name'] = 'أدخل اسمك بالكامل وليس الاسم الأول فقط';
        } elseif (strpos(trim($data['name']), ' ') === strlen(trim($data['name'])) - 1) {
            $this->error['name'] = 'يجب أن لا يحتوي الاسم على مسافة في النهاية';
        }

        if (!isset($data['phone']) || !preg_match('/^01[0-2,5]{1}[0-9]{8}$/', $this->convertArabicNumbersToEnglish($data['phone']))) {
            $this->error['phone'] = 'يجب أن يكون رقم الموبايل صحيح.';
        }

        if (!isset($data['address_1']) || !$data['address_1']) {
            $this->error['address_1'] = 'أدخل اسم الشارع';
        }

        if (!isset($data['address_2']) || !$data['address_2']) {
            $this->error['address_2'] = 'أدخل رقم المبنى';
        }

        if (!isset($data['city']) || !$data['city']) {
            $this->error['city'] = 'أدخل اسم المدينة أو المركز';
        }

        if (!isset($data['zone_id']) || !$data['zone_id']) {
            $this->error['zone'] = 'اختر المحافظة';
        }

        if (!isset($data['payment_method_code']) || !$data['payment_method_code']) {
            $this->error['payment_method_code'] = 'حدد طريقة الدفع المناسبة';
        }

        if (!isset($data['shipping_method_code']) || !$data['shipping_method_code']) {
            $this->error['shipping_method_code'] = 'حدد طريقة الشحن المناسبة';
        }

        $customer_group_id = isset($data['customer_group']) ? (int)$data['customer_group'] : 1;
        $total_amount = $this->cart->getTotal();

    if ($customer_group_id == 2 || ($customer_group_id == 1 && $total_amount >= 25000)) {
            if (empty($data['rin_customer'])) {
                $this->error['rin_customer'] = 'يرجى إدخال الرقم الضريبي أو القومي.';
            } else {
                // تحقق من تنسيق الرقم الضريبي أو القومي (مثال: يجب أن يكون رقمًا مكونًا من 10 إلى 20 رقم)
                if (!preg_match('/^\d{10,20}$/', $data['rin_customer'])) {
                    $this->error['rin_customer'] = 'الرقم الضريبي أو القومي غير صالح.';
                }
            }
        }
    }

private function handleCoupon($data) {
    // إذا أدخل المستخدم شيئًا في خانة الكوبون
    if (isset($data['coupon']) && !empty(trim($data['coupon']))) {
        // حمّل موديل الكوبون
        $this->load->model('extension/total/coupon');
        
        // ابحث عن تفاصيل الكوبون
        $coupon_info = $this->model_extension_total_coupon->getCoupon($data['coupon']);
        
        if ($coupon_info) {
            // كوبون صالح
            $this->session->data['coupon'] = $data['coupon'];
        } else {
            // الكوبون غير صالح
            unset($this->session->data['coupon']);
            $this->error['coupon'] = $this->language->get('error_coupon');
        }
    } else {
        // إذا الحقل فارغ (لم يُدخل شيء)
        unset($this->session->data['coupon']);
        // انتبه: لا نضع أي خطأ هنا لأن الكوبون ليس إجباريًّا.
    }
}


private function calculateTotals() {
    $this->load->model('setting/extension');
    
    $totals = array();
    $taxes = $this->cart->getTaxes();
    $total = 0;

    $sort_order = array();

    $results = $this->model_setting_extension->getExtensions('total');

    foreach ($results as $key => $value) {
        $sort_order[$key] = $this->config->get('total_' . $value['code'] . '_sort_order');
    }

    array_multisort($sort_order, SORT_ASC, $results);

    foreach ($results as $result) {
        if ($this->config->get('total_' . $result['code'] . '_status')) {
            $this->load->model('extension/total/' . $result['code']);
            $this->{'model_extension_total_' . $result['code']}->getTotal([
                'totals' => &$totals,
                'taxes'  => &$taxes,
                'total'  => &$total
            ]);
        }
    }

    $sort_order = array();

    foreach ($totals as $key => $value) {
        $sort_order[$key] = $value['sort_order'];
    }

    array_multisort($sort_order, SORT_ASC, $totals);

    $formatted_totals = array();
    foreach ($totals as $total_item) {
        $formatted_totals[] = array(
            'code'  => $total_item['code'],
            'title' => $total_item['title'],
            'value' => $total_item['value'],
            'text'  => $this->currency->format($total_item['value'], $this->session->data['currency'])
        );
    }

    return array(
        'totals' => $formatted_totals,
        'total' => $total
    );
}


   private function getShippingMethods() {
        $shipping_methods = [];
        $address = [
            'country_id' => 63,
            'zone_id' => isset($this->session->data['quick_checkout']['zone_id']) ? $this->session->data['quick_checkout']['zone_id'] : 1011
        ];
        $results = $this->model_setting_extension->getExtensionsByType('shipping');
        foreach ($results as $result) {
            if ($this->config->get('shipping_' . $result['code'] . '_status')) {
                $this->load->model('extension/shipping/' . $result['code']);
                $quote_data = $this->{'model_extension_shipping_' . $result['code']}->getQuote($address);
                if ($quote_data) {
                    foreach ($quote_data['quote'] as $quote) {
                        $shipping_methods[] = [
                            'title' => $quote['title'],
                            'code' => $quote['code'],
                            'cost' => $quote['cost'],
                            'tax_class_id' => isset($quote['tax_class_id']) ? $quote['tax_class_id'] : 0,
                           'text' => $quote['text']
                        ];
                    }
                }
            }
        }

    $sort_order = array();
    foreach ($shipping_methods as $key => $value) {
        $sort_order[$key] = $value['title'];
    }
    array_multisort($sort_order, SORT_ASC, $shipping_methods);

    return $shipping_methods;
    
    }
    

private function getPaymentMethods() {
    $this->load->model('setting/extension');
    
    $payment_methods = array();
    
    $results = $this->model_setting_extension->getExtensions('payment');

    foreach ($results as $result) {
        if ($this->config->get('payment_' . $result['code'] . '_status')) {
            $this->load->model('extension/payment/' . $result['code']);

            $method = $this->{'model_extension_payment_' . $result['code']}->getMethod($this->session->data['payment_address'], $this->cart->getTotal());

            if ($method) {
                $payment_methods[] = array(
                    'code' => $result['code'],
                    'title' => $method['title'],
                    'terms' => isset($method['terms']) ? $method['terms'] : '',
                    'sort_order' => $method['sort_order']
                );
            }
        }
    }

    $sort_order = array();
    foreach ($payment_methods as $key => $value) {
        $sort_order[$key] = $value['sort_order'];
    }
    array_multisort($sort_order, SORT_ASC, $payment_methods);

    return $payment_methods;
}

    private function getCartData() {
        $this->load->model('tool/image');
        $this->load->model('catalog/product');

        $cart_products = array();
        foreach ($this->cart->getProducts() as $product) {
            if ($product['image']) {
                $image = $this->model_tool_image->resize($product['image'], 100, 100);
            } else {
                $image = '';
            }

            $unit_name = $this->model_catalog_product->getUnitName($product['unit_id']);
            $cart_products[] = array(
                'cart_id'   => $product['cart_id'],
                'thumb'     => $image,
                'name'      => $product['name'],
                'model'     => $product['model'],
                'unit'      => $unit_name,
                'option'    => $product['option'],
                'quantity'  => $product['quantity'],
                'price'     => $this->currency->format($product['price'], $this->session->data['currency']),
                'total'     => $this->currency->format($product['total'], $this->session->data['currency']),
                'href'      => $this->url->link('product/product', 'product_id=' . $product['product_id'])
            );
        }

        $totals = $this->calculateTotals();

        return array(
            'products'    => $cart_products,
            'total_items' => $this->cart->countProducts(),
            'total'       => $this->currency->format($this->cart->getTotal(), $this->session->data['currency']),
            'totals'      => $totals
        );
    }

    private function getOrderData() {
        $this->load->model('setting/extension');
        $this->load->model('localisation/country');
        $this->load->model('localisation/zone');

        $country_info = $this->model_localisation_country->getCountry($this->session->data['payment_address']['country_id']);
        $zone_info = $this->model_localisation_zone->getZone($this->session->data['payment_address']['zone_id']);
        
        $taxes = $this->cart->getTaxes();
        $totals = $this->session->data['quick_checkout']['totals'];
        $total_tax = array_sum($taxes);

        $shipping_method_code = $this->session->data['quick_checkout']['shipping_method_code'];
        $this->session->data['shipping_method']['code'] = $shipping_method_code;

        $shipping_method_name = '';
        foreach ($this->getShippingMethods() as $method) {
            if ($method['code'] == $shipping_method_code) {
                $shipping_method_name = $method['title'];
                break;
            }
        }

        $payment_method_code = $this->session->data['quick_checkout']['payment_method_code'];
        $this->session->data['payment_method']['code'] = $payment_method_code;
        $payment_method_name = '';
        foreach ($this->getPaymentMethods() as $method) {
            if ($method['code'] == $payment_method_code) {
                $payment_method_name = $method['title'];
                break;
            }
        }

        $orderproducts = array();
        foreach ($this->cart->getProducts() as $product) {
            $option_data = array();
            foreach ($product['option'] as $option) {
                $option_data[] = array(
                    'product_option_id' => $option['product_option_id'],
                    'product_option_value_id' => $option['product_option_value_id'],
                    'option_id' => $option['option_id'],
                    'option_value_id' => $option['option_value_id'],
                    'name' => $option['name'],
                    'value' => $option['value'],
                    'type' => $option['type']
                );
            }

            $orderproducts[] = array(
                'product_id' => $product['product_id'],
                'name' => $product['name'],
                'model' => $product['model'],
                'unit_id' => $product['unit_id'],
                'option' => $option_data,
                'quantity' => $product['quantity'],
                'subtract' => $product['subtract'],
                'price' => $product['price'],
                'total' => $product['total'],
                'tax' => $this->tax->getTax($product['price'], $product['tax_class_id']),
                'reward' => $product['reward']
            );
        }

        return [
            'invoice_prefix' => $this->config->get('config_invoice_prefix'),
            'store_id' => $this->config->get('config_store_id'),
            'store_name' => $this->config->get('config_name'),
            'store_url' => $this->config->get('config_url'),
            'customer_id' => $this->customer->getId(),
            'customer_group_id' => $this->session->data['quick_checkout']['customer_group'],
            'firstname' => $this->request->post['name'],
            'lastname' => '',
            'email' => isset($this->request->post['email']) ? $this->request->post['email'] : "",
            'telephone' => $this->request->post['phone'],
            'rin_customer' => isset($this->request->post['rin_customer']) ? $this->request->post['rin_customer'] : '',
            'custom_field' => [],
            'payment_firstname' => $this->request->post['name'],
            'payment_lastname' => '',
            'payment_company' => '',
            'payment_address_1' => $this->request->post['address_1'],
            'payment_address_2' => $this->request->post['address_2'],
            'payment_city' => $this->request->post['city'],
            'payment_postcode' => '',
            'payment_country' => $country_info['name'],
            'payment_country_id' => 63,
            'payment_zone' => $zone_info['name'],
            'payment_zone_id' => $this->session->data['quick_checkout']['zone_id'],
            'payment_address_format' => '',
            'payment_custom_field' => [],
            'payment_method' => $payment_method_name,
            'payment_code' => $payment_method_code,
            'shipping_firstname' => $this->request->post['name'],
            'shipping_lastname' => '',
            'shipping_company' => '',
            'shipping_address_1' => $this->request->post['address_1'],
            'shipping_address_2' => $this->request->post['address_2'],
            'shipping_city' => $this->request->post['city'],
            'shipping_postcode' => '',
            'shipping_country' => $country_info['name'],
            'shipping_country_id' => 63,
            'shipping_zone' => $zone_info['name'],
            'shipping_zone_id' => $this->session->data['quick_checkout']['zone_id'],
            'shipping_address_format' => '',
            'shipping_custom_field' => [],
            'shipping_method' => $shipping_method_name,
            'shipping_code' => $this->session->data['quick_checkout']['shipping_method_code'],
            'comment' => isset($this->request->post['comment']) ? $this->request->post['comment'] : '',
            'total' => $this->session->data['quick_checkout']['total'],
            'tax' => $total_tax,
            'affiliate_id' => 0,
            'commission' => 0,
            'marketing_id' => 0,
            'tracking' => '',
            'language_id' => $this->config->get('config_language_id'),
            'currency_id' => $this->currency->getId($this->session->data['currency']),
            'currency_code' => $this->session->data['currency'],
            'currency_value' => $this->currency->getValue($this->session->data['currency']),
            'ip' => $this->request->server['REMOTE_ADDR'],
            'forwarded_ip' => isset($this->request->server['HTTP_X_FORWARDED_FOR']) ? $this->request->server['HTTP_X_FORWARDED_FOR'] : '',
            'user_agent' => isset($this->request->server['HTTP_USER_AGENT']) ? $this->request->server['HTTP_USER_AGENT'] : '',
            'accept_language' => isset($this->request->server['HTTP_ACCEPT_LANGUAGE']) ? $this->request->server['HTTP_ACCEPT_LANGUAGE'] : '',
            'products' => $orderproducts,
            'vouchers' => [],
            'totals' => $totals
        ];
    }

    private function validateOrder() {
        $this->error = [];

        if (!isset($this->request->post['name']) || !$this->request->post['name']) {
            $this->error['name'] = 'أدخل الاسم بشكل صحيح';
        } elseif (count(explode(' ', trim($this->request->post['name']))) < 2) {
            $this->error['name'] = 'أدخل اسمك بالكامل وليس الاسم الأول فقط';
        } elseif (strpos(trim($this->request->post['name']), ' ') === strlen(trim($this->request->post['name'])) - 1) {
            $this->error['name'] = 'يجب أن لا يحتوي الاسم على مسافة في النهاية';
        }

        if (!isset($this->request->post['phone']) || !preg_match('/^01[0-2,5]{1}[0-9]{8}$/', $this->convertArabicNumbersToEnglish($this->request->post['phone']))) {
            $this->error['phone'] = 'يجب أن يكون رقم الموبايل صحيح.';
        }

        if (!isset($this->request->post['address_1']) || !$this->request->post['address_1']) {
            $this->error['address_1'] = 'أدخل اسم الشارع';
        }

        if (!isset($this->request->post['address_2']) || !$this->request->post['address_2']) {
            $this->error['address_2'] = 'أدخل رقم المبنى';
        }

        if (!isset($this->request->post['city']) || !$this->request->post['city']) {
            $this->error['city'] = 'أدخل اسم المدينة أو المركز';
        }

        if (!isset($this->request->post['zone_id']) || !$this->request->post['zone_id']) {
            $this->error['zone'] = 'اختر المحافظة';
        }

        if (!isset($this->request->post['payment_method_code']) || !$this->request->post['payment_method_code']) {
            $this->error['payment_method_code'] = 'حدد طريقة الدفع المناسبة';
        }

        if (!isset($this->request->post['shipping_method_code']) || !$this->request->post['shipping_method_code']) {
            $this->error['shipping_method_code'] = 'حدد طريقة الشحن المناسبة';
        }

        $customer_group_id = isset($this->request->post['customer_group']) ? (int)$this->request->post['customer_group'] : 1;
        $total_amount = $this->cart->getTotal();

    if ($customer_group_id == 2 || ($customer_group_id == 1 && $total_amount >= 25000)) {
            if (empty($this->request->post['rin_customer'])) {
                $this->error['rin_customer'] = 'يرجى إدخال الرقم الضريبي أو القومي.';
            } else {
                // تحقق من تنسيق الرقم الضريبي أو القومي (مثال: يجب أن يكون رقمًا مكونًا من 10 إلى 20 رقم)
                if (!preg_match('/^\d{10,20}$/', $this->request->post['rin_customer'])) {
                    $this->error['rin_customer'] = 'الرقم الضريبي أو القومي غير صالح.';
                }
            }
        }

        return empty($this->error);
    }

    private function convertArabicNumbersToEnglish($string) {
        $arabic_numbers = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];
        $english_numbers = ['0', '1', '2', '3',  '4', '5', '6', '7', '8', '9'];
        return str_replace($arabic_numbers, $english_numbers, $string);
    }

    public function login() {
        $this->load->language('account/login');
        $this->load->model('account/customer');
        $this->load->model('account/address');

        $json = [];

        if (!isset($this->request->post['email-form'])) {
            $this->error['email-form'] = $this->language->get('error_login');
        }

        if (!isset($this->request->post['password-form']) || empty($this->request->post['password-form'])) {
            $this->error['password-form'] = $this->language->get('error_login');
        }

        if (!$this->error) {
            if ($this->customer->login($this->request->post['email-form'], $this->request->post['password-form'])) {
                $this->model_account_customer->deleteLoginAttempts($this->request->post['email-form']);
                
                $customer_info = $this->model_account_customer->getCustomer($this->customer->getId());
                $this->session->data['quick_checkout']['name'] = $customer_info['firstname'] . ' ' . $customer_info['lastname'];
                $this->session->data['quick_checkout']['phone'] = $customer_info['telephone'];
                $this->session->data['quick_checkout']['email'] = $customer_info['email'];
                $this->session->data['quick_checkout']['customer_group'] = $customer_info['customer_group_id'];

                $default_address = $this->model_account_address->getAddress($this->customer->getAddressId());
                if ($default_address) {
                    $this->session->data['quick_checkout']['address_1'] = $default_address['address_1'];
                    $this->session->data['quick_checkout']['address_2'] = $default_address['address_2'];
                    $this->session->data['quick_checkout']['city'] = $default_address['city'];
                    $this->session->data['quick_checkout']['zone_id'] = $default_address['zone_id'];
                }

                $json['success'] = true;
            } else {
                $this->error['password-form'] = $this->language->get('error_login');
                $this->model_account_customer->addLoginAttempt($this->request->post['email-form']);
            }
        }

        if ($this->error) {
            $json['errors'] = $this->error;
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function applyCoupon() {
        $this->load->language('extension/total/coupon');

        $json = array();

        if (isset($this->request->post['coupon'])) {
            $coupon = $this->request->post['coupon'];
        } else {
            $coupon = '';
            unset($this->session->data['coupon']);
        }

        $this->load->model('extension/total/coupon');

        $coupon_info = $this->model_extension_total_coupon->getCoupon($coupon);

        if (empty($this->request->post['coupon'])) {
            $json['error'] = $this->language->get('error_empty');

            unset($this->session->data['coupon']);
        } elseif ($coupon_info) {
            $this->session->data['coupon'] = $this->request->post['coupon'];

            $this->session->data['success'] = $this->language->get('text_success');

            $json['redirect'] = $this->url->link('checkout/cart');
        } else {
            $json['error'] = $this->language->get('error_coupon');
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
}