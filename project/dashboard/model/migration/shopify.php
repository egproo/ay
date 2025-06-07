<?php
namespace Opencart\Admin\Model\Migration;

class Shopify extends \Opencart\System\Engine\Model {
    private $apiUrl;
    private $accessToken;
    private $apiVersion = '2023-10';

    public function processShopifyImport($data) {
        try {
            $this->apiUrl = rtrim($data['shop_domain'], '/') . '.myshopify.com/admin/api/' . $this->apiVersion;
            $this->accessToken = $data['access_token'];

            // Test connection first
            if (!$this->testConnection()) {
                throw new \Exception('فشل الاتصال بمتجر Shopify');
            }

            // Import core data
            $products = $this->fetchProducts();
            $customers = $this->fetchCustomers();
            $orders = $this->fetchOrders();
            $collections = $this->fetchCollections();
            $discounts = $this->fetchDiscounts();

            return [
                'success' => true,
                'total_records' => count($products) + count($customers) + count($orders) + count($collections) + count($discounts),
                'data' => [
                    'product' => $products,
                    'customer' => $customers,
                    'order' => $orders,
                    'collection' => $collections,
                    'discount' => $discounts
                ]
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    private function testConnection() {
        try {
            $response = $this->makeRequest('products.json', ['limit' => 1]);
            return isset($response['products']);
        } catch (\Exception $e) {
            return false;
        }
    }

    private function fetchProducts() {
        $products = [];
        $sinceId = 0;
        $limit = 250;

        do {
            $params = ['limit' => $limit];
            if ($sinceId > 0) {
                $params['since_id'] = $sinceId;
            }

            $response = $this->makeRequest('products.json', $params);
            
            if (isset($response['products']) && !empty($response['products'])) {
                foreach ($response['products'] as $product) {
                    $products[] = [
                        'shopify_id' => $product['id'],
                        'title' => $product['title'],
                        'body_html' => $product['body_html'],
                        'vendor' => $product['vendor'],
                        'product_type' => $product['product_type'],
                        'created_at' => $product['created_at'],
                        'updated_at' => $product['updated_at'],
                        'published_at' => $product['published_at'],
                        'template_suffix' => $product['template_suffix'],
                        'status' => $product['status'],
                        'published_scope' => $product['published_scope'],
                        'tags' => $product['tags'],
                        'admin_graphql_api_id' => $product['admin_graphql_api_id'],
                        'variants' => json_encode($product['variants']),
                        'options' => json_encode($product['options']),
                        'images' => json_encode($product['images']),
                        'image' => json_encode($product['image'])
                    ];
                    $sinceId = $product['id'];
                }
            }
        } while (isset($response['products']) && count($response['products']) == $limit);

        return $products;
    }

    private function fetchCustomers() {
        $customers = [];
        $sinceId = 0;
        $limit = 250;

        do {
            $params = ['limit' => $limit];
            if ($sinceId > 0) {
                $params['since_id'] = $sinceId;
            }

            $response = $this->makeRequest('customers.json', $params);
            
            if (isset($response['customers']) && !empty($response['customers'])) {
                foreach ($response['customers'] as $customer) {
                    $customers[] = [
                        'shopify_id' => $customer['id'],
                        'email' => $customer['email'],
                        'accepts_marketing' => $customer['accepts_marketing'],
                        'created_at' => $customer['created_at'],
                        'updated_at' => $customer['updated_at'],
                        'first_name' => $customer['first_name'],
                        'last_name' => $customer['last_name'],
                        'orders_count' => $customer['orders_count'],
                        'state' => $customer['state'],
                        'total_spent' => $customer['total_spent'],
                        'last_order_id' => $customer['last_order_id'],
                        'note' => $customer['note'],
                        'verified_email' => $customer['verified_email'],
                        'multipass_identifier' => $customer['multipass_identifier'],
                        'tax_exempt' => $customer['tax_exempt'],
                        'phone' => $customer['phone'],
                        'tags' => $customer['tags'],
                        'last_order_name' => $customer['last_order_name'],
                        'currency' => $customer['currency'],
                        'addresses' => json_encode($customer['addresses']),
                        'accepts_marketing_updated_at' => $customer['accepts_marketing_updated_at'],
                        'marketing_opt_in_level' => $customer['marketing_opt_in_level'],
                        'tax_exemptions' => json_encode($customer['tax_exemptions']),
                        'email_marketing_consent' => json_encode($customer['email_marketing_consent']),
                        'sms_marketing_consent' => json_encode($customer['sms_marketing_consent']),
                        'admin_graphql_api_id' => $customer['admin_graphql_api_id'],
                        'default_address' => json_encode($customer['default_address'])
                    ];
                    $sinceId = $customer['id'];
                }
            }
        } while (isset($response['customers']) && count($response['customers']) == $limit);

        return $customers;
    }

    private function fetchOrders() {
        $orders = [];
        $sinceId = 0;
        $limit = 250;

        do {
            $params = [
                'limit' => $limit,
                'status' => 'any'
            ];
            if ($sinceId > 0) {
                $params['since_id'] = $sinceId;
            }

            $response = $this->makeRequest('orders.json', $params);
            
            if (isset($response['orders']) && !empty($response['orders'])) {
                foreach ($response['orders'] as $order) {
                    $orders[] = [
                        'shopify_id' => $order['id'],
                        'admin_graphql_api_id' => $order['admin_graphql_api_id'],
                        'app_id' => $order['app_id'],
                        'browser_ip' => $order['browser_ip'],
                        'buyer_accepts_marketing' => $order['buyer_accepts_marketing'],
                        'cancel_reason' => $order['cancel_reason'],
                        'cancelled_at' => $order['cancelled_at'],
                        'cart_token' => $order['cart_token'],
                        'checkout_id' => $order['checkout_id'],
                        'checkout_token' => $order['checkout_token'],
                        'closed_at' => $order['closed_at'],
                        'confirmed' => $order['confirmed'],
                        'contact_email' => $order['contact_email'],
                        'created_at' => $order['created_at'],
                        'currency' => $order['currency'],
                        'current_subtotal_price' => $order['current_subtotal_price'],
                        'current_subtotal_price_set' => json_encode($order['current_subtotal_price_set']),
                        'current_total_discounts' => $order['current_total_discounts'],
                        'current_total_discounts_set' => json_encode($order['current_total_discounts_set']),
                        'current_total_duties_set' => json_encode($order['current_total_duties_set']),
                        'current_total_price' => $order['current_total_price'],
                        'current_total_price_set' => json_encode($order['current_total_price_set']),
                        'current_total_tax' => $order['current_total_tax'],
                        'current_total_tax_set' => json_encode($order['current_total_tax_set']),
                        'customer_locale' => $order['customer_locale'],
                        'device_id' => $order['device_id'],
                        'discount_codes' => json_encode($order['discount_codes']),
                        'email' => $order['email'],
                        'estimated_taxes' => $order['estimated_taxes'],
                        'financial_status' => $order['financial_status'],
                        'fulfillment_status' => $order['fulfillment_status'],
                        'gateway' => $order['gateway'],
                        'landing_site' => $order['landing_site'],
                        'landing_site_ref' => $order['landing_site_ref'],
                        'location_id' => $order['location_id'],
                        'name' => $order['name'],
                        'note' => $order['note'],
                        'note_attributes' => json_encode($order['note_attributes']),
                        'number' => $order['number'],
                        'order_number' => $order['order_number'],
                        'order_status_url' => $order['order_status_url'],
                        'original_total_duties_set' => json_encode($order['original_total_duties_set']),
                        'payment_gateway_names' => json_encode($order['payment_gateway_names']),
                        'phone' => $order['phone'],
                        'presentment_currency' => $order['presentment_currency'],
                        'processed_at' => $order['processed_at'],
                        'processing_method' => $order['processing_method'],
                        'reference' => $order['reference'],
                        'referring_site' => $order['referring_site'],
                        'source_identifier' => $order['source_identifier'],
                        'source_name' => $order['source_name'],
                        'source_url' => $order['source_url'],
                        'subtotal_price' => $order['subtotal_price'],
                        'subtotal_price_set' => json_encode($order['subtotal_price_set']),
                        'tags' => $order['tags'],
                        'tax_lines' => json_encode($order['tax_lines']),
                        'taxes_included' => $order['taxes_included'],
                        'test' => $order['test'],
                        'token' => $order['token'],
                        'total_discounts' => $order['total_discounts'],
                        'total_discounts_set' => json_encode($order['total_discounts_set']),
                        'total_line_items_price' => $order['total_line_items_price'],
                        'total_line_items_price_set' => json_encode($order['total_line_items_price_set']),
                        'total_outstanding' => $order['total_outstanding'],
                        'total_price' => $order['total_price'],
                        'total_price_set' => json_encode($order['total_price_set']),
                        'total_price_usd' => $order['total_price_usd'],
                        'total_shipping_price_set' => json_encode($order['total_shipping_price_set']),
                        'total_tax' => $order['total_tax'],
                        'total_tax_set' => json_encode($order['total_tax_set']),
                        'total_tip_received' => $order['total_tip_received'],
                        'total_weight' => $order['total_weight'],
                        'updated_at' => $order['updated_at'],
                        'user_id' => $order['user_id'],
                        'billing_address' => json_encode($order['billing_address']),
                        'customer' => json_encode($order['customer']),
                        'discount_applications' => json_encode($order['discount_applications']),
                        'fulfillments' => json_encode($order['fulfillments']),
                        'line_items' => json_encode($order['line_items']),
                        'payment_terms' => json_encode($order['payment_terms']),
                        'refunds' => json_encode($order['refunds']),
                        'shipping_address' => json_encode($order['shipping_address']),
                        'shipping_lines' => json_encode($order['shipping_lines'])
                    ];
                    $sinceId = $order['id'];
                }
            }
        } while (isset($response['orders']) && count($response['orders']) == $limit);

        return $orders;
    }

    private function fetchCollections() {
        $collections = [];
        $sinceId = 0;
        $limit = 250;

        do {
            $params = ['limit' => $limit];
            if ($sinceId > 0) {
                $params['since_id'] = $sinceId;
            }

            $response = $this->makeRequest('custom_collections.json', $params);
            
            if (isset($response['custom_collections']) && !empty($response['custom_collections'])) {
                foreach ($response['custom_collections'] as $collection) {
                    $collections[] = [
                        'shopify_id' => $collection['id'],
                        'handle' => $collection['handle'],
                        'title' => $collection['title'],
                        'updated_at' => $collection['updated_at'],
                        'body_html' => $collection['body_html'],
                        'published_at' => $collection['published_at'],
                        'sort_order' => $collection['sort_order'],
                        'template_suffix' => $collection['template_suffix'],
                        'published_scope' => $collection['published_scope'],
                        'admin_graphql_api_id' => $collection['admin_graphql_api_id'],
                        'image' => json_encode($collection['image'])
                    ];
                    $sinceId = $collection['id'];
                }
            }
        } while (isset($response['custom_collections']) && count($response['custom_collections']) == $limit);

        return $collections;
    }

    private function fetchDiscounts() {
        $discounts = [];
        $sinceId = 0;
        $limit = 250;

        do {
            $params = ['limit' => $limit];
            if ($sinceId > 0) {
                $params['since_id'] = $sinceId;
            }

            $response = $this->makeRequest('discount_codes.json', $params);
            
            if (isset($response['discount_codes']) && !empty($response['discount_codes'])) {
                foreach ($response['discount_codes'] as $discount) {
                    $discounts[] = [
                        'shopify_id' => $discount['id'],
                        'price_rule_id' => $discount['price_rule_id'],
                        'code' => $discount['code'],
                        'usage_count' => $discount['usage_count'],
                        'created_at' => $discount['created_at'],
                        'updated_at' => $discount['updated_at']
                    ];
                    $sinceId = $discount['id'];
                }
            }
        } while (isset($response['discount_codes']) && count($response['discount_codes']) == $limit);

        return $discounts;
    }

    private function makeRequest($endpoint, $params = []) {
        $url = 'https://' . $this->apiUrl . '/' . $endpoint;
        
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'X-Shopify-Access-Token: ' . $this->accessToken,
                'Content-Type: application/json',
                'Accept: application/json'
            ],
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => true
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new \Exception('خطأ في الاتصال: ' . $error);
        }

        if ($httpCode >= 400) {
            $errorData = json_decode($response, true);
            $errorMessage = isset($errorData['errors']) ? 
                (is_array($errorData['errors']) ? implode(', ', $errorData['errors']) : $errorData['errors']) : 
                'خطأ HTTP: ' . $httpCode;
            throw new \Exception($errorMessage);
        }

        $result = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('خطأ في تحليل البيانات المستلمة');
        }

        return $result;
    }
}
