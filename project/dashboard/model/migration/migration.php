<?php
namespace Opencart\Admin\Model\Migration;

class Migration extends \Opencart\System\Engine\Model {
    public function createMigration($data) {
        $this->db->query("INSERT INTO " . DB_PREFIX . "migration SET 
            source = '" . $this->db->escape($data['source']) . "',
            store_url = '" . $this->db->escape($data['store_url']) . "',
            total_records = '" . (int)$data['total_records'] . "',
            status = '" . $this->db->escape($data['status']) . "',
            date_added = NOW()");
        
        return $this->db->getLastId();
    }

    public function storeTemporaryData($migration_id, $data) {
        foreach ($data as $table => $records) {
            foreach ($records as $record) {
                $this->db->query("INSERT INTO " . DB_PREFIX . "migration_temp_" . $table . " 
                    SET migration_id = '" . (int)$migration_id . "',
                    source_data = '" . $this->db->escape(json_encode($record)) . "',
                    status = 'pending'");
            }
        }
    }

    public function validateData($migration_id, $table) {
        $this->load->model('migration/validation');
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "migration_temp_" . $table . "
            WHERE migration_id = '" . (int)$migration_id . "' AND status = 'pending'");

        $errors = [];
        foreach ($query->rows as $row) {
            $source_data = json_decode($row['source_data'], true);
            $validation_result = $this->model_migration_validation->{'validate' . ucfirst($table)}($source_data);
            
            if (!$validation_result['valid']) {
                $errors[] = $validation_result['error'];
            }
        }

        return $errors;
    }

    public function processData($migration_id, $table) {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "migration_temp_" . $table . "
            WHERE migration_id = '" . (int)$migration_id . "' AND status = 'pending'");

        foreach ($query->rows as $row) {
            $source_data = json_decode($row['source_data'], true);
            $processed = $this->{'process' . ucfirst($table)}($source_data);
            
            if ($processed) {
                $this->db->query("UPDATE " . DB_PREFIX . "migration_temp_" . $table . "
                    SET status = 'completed'
                    WHERE migration_temp_id = '" . (int)$row['migration_temp_id'] . "'");
            }
        }
    }

    private function processProduct($data) {
        try {
            $product_data = [
                'name' => $data['name'],
                'description' => $data['description'] ?? '',
                'meta_title' => $data['name'],
                'model' => $data['default_code'] ?? '',
                'price' => $data['list_price'] ?? 0,
                'quantity' => $data['qty_available'] ?? 0,
                'status' => 1
            ];

            $this->db->query("INSERT INTO " . DB_PREFIX . "product SET 
                model = '" . $this->db->escape($product_data['model']) . "',
                quantity = '" . (int)$product_data['quantity'] . "',
                price = '" . (float)$product_data['price'] . "',
                status = '" . (int)$product_data['status'] . "',
                date_added = NOW()");

            $product_id = $this->db->getLastId();

            $this->db->query("INSERT INTO " . DB_PREFIX . "product_description SET 
                product_id = '" . (int)$product_id . "',
                language_id = '" . (int)$this->config->get('config_language_id') . "',
                name = '" . $this->db->escape($product_data['name']) . "',
                description = '" . $this->db->escape($product_data['description']) . "',
                meta_title = '" . $this->db->escape($product_data['meta_title']) . "'");

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    private function processCustomer($data) {
        try {
            $customer_data = [
                'firstname' => $data['name'],
                'email' => $data['email'] ?? '',
                'telephone' => $data['phone'] ?? '',
                'status' => 1,
                'safe' => 1
            ];

            $this->db->query("INSERT INTO " . DB_PREFIX . "customer SET 
                firstname = '" . $this->db->escape($customer_data['firstname']) . "',
                email = '" . $this->db->escape($customer_data['email']) . "',
                telephone = '" . $this->db->escape($customer_data['telephone']) . "',
                status = '" . (int)$customer_data['status'] . "',
                safe = '" . (int)$customer_data['safe'] . "',
                date_added = NOW()");

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    private function processOrder($data) {
        try {
            $order_data = [
                'invoice_prefix' => 'INV-' . date('Y') . '-00',
                'store_id' => 0,
                'store_name' => $this->config->get('config_name'),
                'customer_id' => $data['partner_id'][0] ?? 0,
                'total' => $data['amount_total'] ?? 0,
                'order_status_id' => 1
            ];

            $this->db->query("INSERT INTO " . DB_PREFIX . "order SET 
                invoice_prefix = '" . $this->db->escape($order_data['invoice_prefix']) . "',
                store_id = '" . (int)$order_data['store_id'] . "',
                store_name = '" . $this->db->escape($order_data['store_name']) . "',
                customer_id = '" . (int)$order_data['customer_id'] . "',
                total = '" . (float)$order_data['total'] . "',
                order_status_id = '" . (int)$order_data['order_status_id'] . "',
                date_added = NOW()");

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function processWooCommerceImport($data) {
        try {
            // التحقق من بيانات الاعتماد
            $api_url = rtrim($data['store_url'], '/') . '/wp-json/wc/v3/';
            $headers = [
                'Authorization' => 'Basic ' . base64_encode($data['consumer_key'] . ':' . $data['consumer_secret'])
            ];

            // استيراد المنتجات
            $products = $this->fetchWooCommerceData($api_url . 'products', $headers);
            
            // استيراد العملاء
            $customers = $this->fetchWooCommerceData($api_url . 'customers', $headers);
            
            // استيراد الطلبات
            $orders = $this->fetchWooCommerceData($api_url . 'orders', $headers);

            return [
                'success' => true,
                'total_records' => count($products) + count($customers) + count($orders),
                'data' => [
                    'product' => $products,
                    'customer' => $customers,
                    'order' => $orders
                ]
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    private function fetchWooCommerceData($endpoint, $headers) {
        $page = 1;
        $per_page = 100;
        $all_data = [];

        do {
            $response = $this->makeApiRequest($endpoint, [
                'page' => $page,
                'per_page' => $per_page
            ], $headers);

            if (!empty($response)) {
                $all_data = array_merge($all_data, $response);
                $page++;
            }
        } while (!empty($response) && count($response) == $per_page);

        return $all_data;
    }

    private function makeApiRequest($url, $params, $headers) {
        $curl = curl_init();
        
        $url = $url . '?' . http_build_query($params);
        
        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => array_map(
                function($k, $v) { return "$k: $v"; },
                array_keys($headers),
                $headers
            )
        ]);

        $response = curl_exec($curl);
        $error = curl_error($curl);
        
        curl_close($curl);

        if ($error) {
            throw new \Exception('API Request Error: ' . $error);
        }

        return json_decode($response, true);
    }
}