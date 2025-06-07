<?php
namespace Opencart\Admin\Model\Migration;

class Woocommerce extends \Opencart\System\Engine\Model {
    private $apiUrl;
    private $consumerKey;
    private $consumerSecret;
    private $version = 'wc/v3';

    public function processWooCommerceImport($data) {
        try {
            $this->apiUrl = rtrim($data['store_url'], '/') . '/wp-json/' . $this->version;
            $this->consumerKey = $data['consumer_key'];
            $this->consumerSecret = $data['consumer_secret'];

            // Test connection first
            if (!$this->testConnection()) {
                throw new \Exception('فشل الاتصال بمتجر WooCommerce');
            }

            // Import core data
            $products = $this->fetchProducts();
            $customers = $this->fetchCustomers();
            $orders = $this->fetchOrders();
            $categories = $this->fetchCategories();
            $coupons = $this->fetchCoupons();

            return [
                'success' => true,
                'total_records' => count($products) + count($customers) + count($orders) + count($categories) + count($coupons),
                'data' => [
                    'product' => $products,
                    'customer' => $customers,
                    'order' => $orders,
                    'category' => $categories,
                    'coupon' => $coupons
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
            $response = $this->makeRequest('products', ['per_page' => 1]);
            return !empty($response) || is_array($response);
        } catch (\Exception $e) {
            return false;
        }
    }

    private function fetchProducts() {
        $products = [];
        $page = 1;
        $per_page = 100;

        do {
            $response = $this->makeRequest('products', [
                'page' => $page,
                'per_page' => $per_page,
                'status' => 'any'
            ]);

            if (!empty($response)) {
                foreach ($response as $product) {
                    $products[] = [
                        'wc_id' => $product['id'],
                        'name' => $product['name'],
                        'slug' => $product['slug'],
                        'type' => $product['type'],
                        'status' => $product['status'],
                        'featured' => $product['featured'],
                        'catalog_visibility' => $product['catalog_visibility'],
                        'description' => $product['description'],
                        'short_description' => $product['short_description'],
                        'sku' => $product['sku'],
                        'price' => $product['price'],
                        'regular_price' => $product['regular_price'],
                        'sale_price' => $product['sale_price'],
                        'date_on_sale_from' => $product['date_on_sale_from'],
                        'date_on_sale_to' => $product['date_on_sale_to'],
                        'price_html' => $product['price_html'],
                        'on_sale' => $product['on_sale'],
                        'purchasable' => $product['purchasable'],
                        'total_sales' => $product['total_sales'],
                        'virtual' => $product['virtual'],
                        'downloadable' => $product['downloadable'],
                        'downloads' => json_encode($product['downloads']),
                        'download_limit' => $product['download_limit'],
                        'download_expiry' => $product['download_expiry'],
                        'external_url' => $product['external_url'],
                        'button_text' => $product['button_text'],
                        'tax_status' => $product['tax_status'],
                        'tax_class' => $product['tax_class'],
                        'manage_stock' => $product['manage_stock'],
                        'stock_quantity' => $product['stock_quantity'],
                        'stock_status' => $product['stock_status'],
                        'backorders' => $product['backorders'],
                        'backorders_allowed' => $product['backorders_allowed'],
                        'backordered' => $product['backordered'],
                        'sold_individually' => $product['sold_individually'],
                        'weight' => $product['weight'],
                        'dimensions' => json_encode($product['dimensions']),
                        'shipping_required' => $product['shipping_required'],
                        'shipping_taxable' => $product['shipping_taxable'],
                        'shipping_class' => $product['shipping_class'],
                        'shipping_class_id' => $product['shipping_class_id'],
                        'reviews_allowed' => $product['reviews_allowed'],
                        'average_rating' => $product['average_rating'],
                        'rating_count' => $product['rating_count'],
                        'related_ids' => json_encode($product['related_ids']),
                        'upsell_ids' => json_encode($product['upsell_ids']),
                        'cross_sell_ids' => json_encode($product['cross_sell_ids']),
                        'parent_id' => $product['parent_id'],
                        'purchase_note' => $product['purchase_note'],
                        'categories' => json_encode($product['categories']),
                        'tags' => json_encode($product['tags']),
                        'images' => json_encode($product['images']),
                        'attributes' => json_encode($product['attributes']),
                        'default_attributes' => json_encode($product['default_attributes']),
                        'variations' => json_encode($product['variations']),
                        'grouped_products' => json_encode($product['grouped_products']),
                        'menu_order' => $product['menu_order'],
                        'meta_data' => json_encode($product['meta_data']),
                        'date_created' => $product['date_created'],
                        'date_modified' => $product['date_modified']
                    ];
                }
                $page++;
            }
        } while (!empty($response) && count($response) == $per_page);

        return $products;
    }

    private function fetchCustomers() {
        $customers = [];
        $page = 1;
        $per_page = 100;

        do {
            $response = $this->makeRequest('customers', [
                'page' => $page,
                'per_page' => $per_page
            ]);

            if (!empty($response)) {
                foreach ($response as $customer) {
                    $customers[] = [
                        'wc_id' => $customer['id'],
                        'date_created' => $customer['date_created'],
                        'date_modified' => $customer['date_modified'],
                        'email' => $customer['email'],
                        'first_name' => $customer['first_name'],
                        'last_name' => $customer['last_name'],
                        'role' => $customer['role'],
                        'username' => $customer['username'],
                        'billing' => json_encode($customer['billing']),
                        'shipping' => json_encode($customer['shipping']),
                        'is_paying_customer' => $customer['is_paying_customer'],
                        'avatar_url' => $customer['avatar_url'],
                        'meta_data' => json_encode($customer['meta_data'])
                    ];
                }
                $page++;
            }
        } while (!empty($response) && count($response) == $per_page);

        return $customers;
    }

    private function fetchOrders() {
        $orders = [];
        $page = 1;
        $per_page = 100;

        do {
            $response = $this->makeRequest('orders', [
                'page' => $page,
                'per_page' => $per_page,
                'status' => 'any'
            ]);

            if (!empty($response)) {
                foreach ($response as $order) {
                    $orders[] = [
                        'wc_id' => $order['id'],
                        'parent_id' => $order['parent_id'],
                        'number' => $order['number'],
                        'order_key' => $order['order_key'],
                        'created_via' => $order['created_via'],
                        'version' => $order['version'],
                        'status' => $order['status'],
                        'currency' => $order['currency'],
                        'date_created' => $order['date_created'],
                        'date_modified' => $order['date_modified'],
                        'discount_total' => $order['discount_total'],
                        'discount_tax' => $order['discount_tax'],
                        'shipping_total' => $order['shipping_total'],
                        'shipping_tax' => $order['shipping_tax'],
                        'cart_tax' => $order['cart_tax'],
                        'total' => $order['total'],
                        'total_tax' => $order['total_tax'],
                        'prices_include_tax' => $order['prices_include_tax'],
                        'customer_id' => $order['customer_id'],
                        'customer_ip_address' => $order['customer_ip_address'],
                        'customer_user_agent' => $order['customer_user_agent'],
                        'customer_note' => $order['customer_note'],
                        'billing' => json_encode($order['billing']),
                        'shipping' => json_encode($order['shipping']),
                        'payment_method' => $order['payment_method'],
                        'payment_method_title' => $order['payment_method_title'],
                        'transaction_id' => $order['transaction_id'],
                        'date_paid' => $order['date_paid'],
                        'date_completed' => $order['date_completed'],
                        'cart_hash' => $order['cart_hash'],
                        'meta_data' => json_encode($order['meta_data']),
                        'line_items' => json_encode($order['line_items']),
                        'tax_lines' => json_encode($order['tax_lines']),
                        'shipping_lines' => json_encode($order['shipping_lines']),
                        'fee_lines' => json_encode($order['fee_lines']),
                        'coupon_lines' => json_encode($order['coupon_lines']),
                        'refunds' => json_encode($order['refunds'])
                    ];
                }
                $page++;
            }
        } while (!empty($response) && count($response) == $per_page);

        return $orders;
    }

    private function fetchCategories() {
        $categories = [];
        $page = 1;
        $per_page = 100;

        do {
            $response = $this->makeRequest('products/categories', [
                'page' => $page,
                'per_page' => $per_page
            ]);

            if (!empty($response)) {
                foreach ($response as $category) {
                    $categories[] = [
                        'wc_id' => $category['id'],
                        'name' => $category['name'],
                        'slug' => $category['slug'],
                        'parent' => $category['parent'],
                        'description' => $category['description'],
                        'display' => $category['display'],
                        'image' => json_encode($category['image']),
                        'menu_order' => $category['menu_order'],
                        'count' => $category['count']
                    ];
                }
                $page++;
            }
        } while (!empty($response) && count($response) == $per_page);

        return $categories;
    }

    private function fetchCoupons() {
        $coupons = [];
        $page = 1;
        $per_page = 100;

        do {
            $response = $this->makeRequest('coupons', [
                'page' => $page,
                'per_page' => $per_page
            ]);

            if (!empty($response)) {
                foreach ($response as $coupon) {
                    $coupons[] = [
                        'wc_id' => $coupon['id'],
                        'code' => $coupon['code'],
                        'amount' => $coupon['amount'],
                        'date_created' => $coupon['date_created'],
                        'date_modified' => $coupon['date_modified'],
                        'discount_type' => $coupon['discount_type'],
                        'description' => $coupon['description'],
                        'date_expires' => $coupon['date_expires'],
                        'usage_count' => $coupon['usage_count'],
                        'individual_use' => $coupon['individual_use'],
                        'product_ids' => json_encode($coupon['product_ids']),
                        'excluded_product_ids' => json_encode($coupon['excluded_product_ids']),
                        'usage_limit' => $coupon['usage_limit'],
                        'usage_limit_per_user' => $coupon['usage_limit_per_user'],
                        'limit_usage_to_x_items' => $coupon['limit_usage_to_x_items'],
                        'free_shipping' => $coupon['free_shipping'],
                        'product_categories' => json_encode($coupon['product_categories']),
                        'excluded_product_categories' => json_encode($coupon['excluded_product_categories']),
                        'exclude_sale_items' => $coupon['exclude_sale_items'],
                        'minimum_amount' => $coupon['minimum_amount'],
                        'maximum_amount' => $coupon['maximum_amount'],
                        'email_restrictions' => json_encode($coupon['email_restrictions']),
                        'used_by' => json_encode($coupon['used_by']),
                        'meta_data' => json_encode($coupon['meta_data'])
                    ];
                }
                $page++;
            }
        } while (!empty($response) && count($response) == $per_page);

        return $coupons;
    }

    private function makeRequest($endpoint, $params = []) {
        $url = $this->apiUrl . '/' . $endpoint;
        
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
            CURLOPT_USERPWD => $this->consumerKey . ':' . $this->consumerSecret,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Accept: application/json'
            ],
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => false
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
            $errorMessage = isset($errorData['message']) ? $errorData['message'] : 'خطأ HTTP: ' . $httpCode;
            throw new \Exception($errorMessage);
        }

        $result = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('خطأ في تحليل البيانات المستلمة');
        }

        return $result;
    }
}
