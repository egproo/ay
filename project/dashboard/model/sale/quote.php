<?php
class ModelSaleQuote extends Model {
    public function addQuote($data) {
        $this->db->query("INSERT INTO " . DB_PREFIX . "sales_quotation SET
            quotation_number = '" . $this->db->escape($data['quotation_number']) . "',
            customer_id = '" . (int)$data['customer_id'] . "',
            branch_id = '" . (int)$data['branch_id'] . "',
            quotation_date = '" . $this->db->escape($data['quotation_date']) . "',
            valid_until = '" . $this->db->escape($data['valid_until']) . "',
            status = '" . $this->db->escape($data['status']) . "',
            total_amount = '" . (float)$data['total_amount'] . "',
            discount_amount = '" . (float)$data['discount_amount'] . "',
            tax_amount = '" . (float)$data['tax_amount'] . "',
            net_amount = '" . (float)$data['net_amount'] . "',
            notes = '" . $this->db->escape($data['notes']) . "',
            created_by = '" . (int)$this->user->getId() . "',
            created_at = NOW()");

        $quote_id = $this->db->getLastId();

        // Insert quotation items
        if (isset($data['quote_item']) && is_array($data['quote_item'])) {
            foreach ($data['quote_item'] as $item) {
                $this->db->query("INSERT INTO " . DB_PREFIX . "sales_quotation_item SET
                    quotation_id = '" . (int)$quote_id . "',
                    product_id = '" . (int)$item['product_id'] . "',
                    product_name = '" . $this->db->escape($item['product_name']) . "',
                    unit_id = '" . (int)$item['unit_id'] . "',
                    quantity = '" . (float)$item['quantity'] . "',
                    price = '" . (float)$item['price'] . "',
                    discount_rate = '" . (float)$item['discount_rate'] . "',
                    tax_rate = '" . (float)$item['tax_rate'] . "',
                    total = '" . (float)$item['total'] . "',
                    notes = '" . $this->db->escape($item['notes']) . "'");
            }
        }

        return $quote_id;
    }

    public function editQuote($quote_id, $data) {
        $this->db->query("UPDATE " . DB_PREFIX . "sales_quotation SET
            quotation_number = '" . $this->db->escape($data['quotation_number']) . "',
            customer_id = '" . (int)$data['customer_id'] . "',
            branch_id = '" . (int)$data['branch_id'] . "',
            quotation_date = '" . $this->db->escape($data['quotation_date']) . "',
            valid_until = '" . $this->db->escape($data['valid_until']) . "',
            status = '" . $this->db->escape($data['status']) . "',
            total_amount = '" . (float)$data['total_amount'] . "',
            discount_amount = '" . (float)$data['discount_amount'] . "',
            tax_amount = '" . (float)$data['tax_amount'] . "',
            net_amount = '" . (float)$data['net_amount'] . "',
            notes = '" . $this->db->escape($data['notes']) . "',
            created_at = created_at
            WHERE quotation_id = '" . (int)$quote_id . "'");

        // Delete existing quote items
        $this->db->query("DELETE FROM " . DB_PREFIX . "sales_quotation_item WHERE quotation_id = '" . (int)$quote_id . "'");

        // Insert updated quote items
        if (isset($data['quote_item']) && is_array($data['quote_item'])) {
            foreach ($data['quote_item'] as $item) {
                $this->db->query("INSERT INTO " . DB_PREFIX . "sales_quotation_item SET
                    quotation_id = '" . (int)$quote_id . "',
                    product_id = '" . (int)$item['product_id'] . "',
                    product_name = '" . $this->db->escape($item['product_name']) . "',
                    unit_id = '" . (int)$item['unit_id'] . "',
                    quantity = '" . (float)$item['quantity'] . "',
                    price = '" . (float)$item['price'] . "',
                    discount_rate = '" . (float)$item['discount_rate'] . "',
                    tax_rate = '" . (float)$item['tax_rate'] . "',
                    total = '" . (float)$item['total'] . "',
                    notes = '" . $this->db->escape($item['notes']) . "'");
            }
        }
    }

    public function deleteQuote($quote_id) {
        $this->db->query("DELETE FROM " . DB_PREFIX . "sales_quotation_item WHERE quotation_id = '" . (int)$quote_id . "'");
        $this->db->query("DELETE FROM " . DB_PREFIX . "sales_quotation WHERE quotation_id = '" . (int)$quote_id . "'");
    }

    public function getQuote($quote_id) {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "sales_quotation WHERE quotation_id = '" . (int)$quote_id . "'");
        
        return $query->row;
    }

    public function getQuotes($data = array()) {
        $sql = "SELECT q.*, CONCAT(c.firstname, ' ', IFNULL(c.lastname, '')) AS customer_name 
                FROM " . DB_PREFIX . "sales_quotation q 
                LEFT JOIN " . DB_PREFIX . "customer c ON (q.customer_id = c.customer_id)";

        $where = array();

        if (!empty($data['filter_quote_number'])) {
            $where[] = "q.quotation_number LIKE '%" . $this->db->escape($data['filter_quote_number']) . "%'";
        }

        if (!empty($data['filter_customer'])) {
            $where[] = "CONCAT(c.firstname, ' ', IFNULL(c.lastname, '')) LIKE '%" . $this->db->escape($data['filter_customer']) . "%'";
        }

        if (!empty($data['filter_status'])) {
            $where[] = "q.status = '" . $this->db->escape($data['filter_status']) . "'";
        }

        if (!empty($data['filter_date_start'])) {
            $where[] = "DATE(q.quotation_date) >= '" . $this->db->escape($data['filter_date_start']) . "'";
        }

        if (!empty($data['filter_date_end'])) {
            $where[] = "DATE(q.quotation_date) <= '" . $this->db->escape($data['filter_date_end']) . "'";
        }

        if (!empty($data['filter_total_min'])) {
            $where[] = "q.total_amount >= '" . (float)$data['filter_total_min'] . "'";
        }

        if (!empty($data['filter_total_max'])) {
            $where[] = "q.total_amount <= '" . (float)$data['filter_total_max'] . "'";
        }

        if ($where) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }

        $sort_data = array(
            'q.quotation_number',
            'customer_name',
            'q.status',
            'q.total_amount',
            'q.quotation_date',
            'q.valid_until'
        );

        if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
            $sql .= " ORDER BY " . $data['sort'];
        } else {
            $sql .= " ORDER BY q.quotation_date";
        }

        if (isset($data['order']) && ($data['order'] == 'DESC')) {
            $sql .= " DESC";
        } else {
            $sql .= " ASC";
        }

        if (isset($data['start']) || isset($data['limit'])) {
            if ($data['start'] < 0) {
                $data['start'] = 0;
            }

            if ($data['limit'] < 1) {
                $data['limit'] = 20;
            }

            $sql .= " LIMIT " . (int)$data['start'] . "," . (int)$data['limit'];
        }

        $query = $this->db->query($sql);

        return $query->rows;
    }

    public function getTotalQuotes($data = array()) {
        $sql = "SELECT COUNT(*) AS total FROM " . DB_PREFIX . "sales_quotation q 
                LEFT JOIN " . DB_PREFIX . "customer c ON (q.customer_id = c.customer_id)";

        $where = array();

        if (!empty($data['filter_quote_number'])) {
            $where[] = "q.quotation_number LIKE '%" . $this->db->escape($data['filter_quote_number']) . "%'";
        }

        if (!empty($data['filter_customer'])) {
            $where[] = "CONCAT(c.firstname, ' ', IFNULL(c.lastname, '')) LIKE '%" . $this->db->escape($data['filter_customer']) . "%'";
        }

        if (!empty($data['filter_status'])) {
            $where[] = "q.status = '" . $this->db->escape($data['filter_status']) . "'";
        }

        if (!empty($data['filter_date_start'])) {
            $where[] = "DATE(q.quotation_date) >= '" . $this->db->escape($data['filter_date_start']) . "'";
        }

        if (!empty($data['filter_date_end'])) {
            $where[] = "DATE(q.quotation_date) <= '" . $this->db->escape($data['filter_date_end']) . "'";
        }

        if (!empty($data['filter_total_min'])) {
            $where[] = "q.total_amount >= '" . (float)$data['filter_total_min'] . "'";
        }

        if (!empty($data['filter_total_max'])) {
            $where[] = "q.total_amount <= '" . (float)$data['filter_total_max'] . "'";
        }

        if ($where) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }

        $query = $this->db->query($sql);

        return $query->row['total'];
    }

    public function getQuoteItems($quote_id) {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "sales_quotation_item 
            WHERE quotation_id = '" . (int)$quote_id . "' 
            ORDER BY item_id ASC");
        
        return $query->rows;
    }

    public function updateQuoteStatus($quote_id, $status) {
        $this->db->query("UPDATE " . DB_PREFIX . "sales_quotation SET 
            status = '" . $this->db->escape($status) . "' 
            WHERE quotation_id = '" . (int)$quote_id . "'");
    }

    public function updateQuoteEmailSent($quote_id) {
        $this->db->query("UPDATE " . DB_PREFIX . "sales_quotation SET 
            email_sent_date = NOW() 
            WHERE quotation_id = '" . (int)$quote_id . "'");
    }

    public function convertToOrder($quote_id) {
        $quote_info = $this->getQuote($quote_id);
        
        if (!$quote_info || $quote_info['status'] != 'approved' || $quote_info['converted_to_order']) {
            return false;
        }
        
        $this->load->model('sale/order');
        
        // Load quote items
        $quote_items = $this->getQuoteItems($quote_id);
        
        // Create order data structure
        $order_data = array(
            'invoice_prefix' => $this->config->get('config_invoice_prefix'),
            'store_id' => 0, // Default store
            'store_name' => $this->config->get('config_name'),
            'store_url' => $this->config->get('config_url'),
            'customer_id' => $quote_info['customer_id'],
            'customer_group_id' => $this->getCustomerGroupId($quote_info['customer_id']),
            'firstname' => $this->getCustomerFirstname($quote_info['customer_id']),
            'lastname' => $this->getCustomerLastname($quote_info['customer_id']),
            'email' => $this->getCustomerEmail($quote_info['customer_id']),
            'telephone' => $this->getCustomerTelephone($quote_info['customer_id']),
            'custom_field' => array(),
            'payment_firstname' => $this->getCustomerFirstname($quote_info['customer_id']),
            'payment_lastname' => $this->getCustomerLastname($quote_info['customer_id']),
            'payment_company' => '',
            'payment_address_1' => '',
            'payment_address_2' => '',
            'payment_city' => '',
            'payment_postcode' => '',
            'payment_country' => '',
            'payment_country_id' => 0,
            'payment_zone' => '',
            'payment_zone_id' => 0,
            'payment_address_format' => '',
            'payment_custom_field' => array(),
            'payment_method' => '',
            'payment_code' => '',
            'shipping_firstname' => $this->getCustomerFirstname($quote_info['customer_id']),
            'shipping_lastname' => $this->getCustomerLastname($quote_info['customer_id']),
            'shipping_company' => '',
            'shipping_address_1' => '',
            'shipping_address_2' => '',
            'shipping_city' => '',
            'shipping_postcode' => '',
            'shipping_country' => '',
            'shipping_country_id' => 0,
            'shipping_zone' => '',
            'shipping_zone_id' => 0,
            'shipping_address_format' => '',
            'shipping_custom_field' => array(),
            'shipping_method' => '',
            'shipping_code' => '',
            'comment' => 'تم إنشاؤه من عرض سعر: ' . $quote_info['quotation_number'],
            'total' => $quote_info['total_amount'],
            'affiliate_id' => 0,
            'commission' => 0,
            'marketing_id' => 0,
            'tracking' => '',
            'language_id' => $this->config->get('config_language_id'),
            'currency_id' => $this->currency->getId($this->config->get('config_currency')),
            'currency_code' => $this->config->get('config_currency'),
            'currency_value' => $this->currency->getValue($this->config->get('config_currency')),
            'ip' => $this->request->server['REMOTE_ADDR'],
            'forwarded_ip' => isset($this->request->server['HTTP_X_FORWARDED_FOR']) ? $this->request->server['HTTP_X_FORWARDED_FOR'] : '',
            'user_agent' => isset($this->request->server['HTTP_USER_AGENT']) ? $this->request->server['HTTP_USER_AGENT'] : '',
            'accept_language' => isset($this->request->server['HTTP_ACCEPT_LANGUAGE']) ? $this->request->server['HTTP_ACCEPT_LANGUAGE'] : '',
            'order_posuser_id' => $this->user->getId(),
            'order_posuser_name' => $this->user->getFullName(),
            'branch_id' => $quote_info['branch_id']
        );
        
        // Get address information
        $customer_address = $this->getCustomerAddress($quote_info['customer_id']);
        
        if ($customer_address) {
            $order_data['payment_address_1'] = $customer_address['address_1'];
            $order_data['payment_address_2'] = $customer_address['address_2'];
            $order_data['payment_city'] = $customer_address['city'];
            $order_data['payment_postcode'] = $customer_address['postcode'];
            $order_data['payment_country'] = $customer_address['country'];
            $order_data['payment_country_id'] = $customer_address['country_id'];
            $order_data['payment_zone'] = $customer_address['zone'];
            $order_data['payment_zone_id'] = $customer_address['zone_id'];
            
            $order_data['shipping_address_1'] = $customer_address['address_1'];
            $order_data['shipping_address_2'] = $customer_address['address_2'];
            $order_data['shipping_city'] = $customer_address['city'];
            $order_data['shipping_postcode'] = $customer_address['postcode'];
            $order_data['shipping_country'] = $customer_address['country'];
            $order_data['shipping_country_id'] = $customer_address['country_id'];
            $order_data['shipping_zone'] = $customer_address['zone'];
            $order_data['shipping_zone_id'] = $customer_address['zone_id'];
        }
        
        // Add products to order
        $order_data['products'] = array();
        
        foreach ($quote_items as $item) {
            $this->load->model('catalog/product');
            $product_info = $this->model_catalog_product->getProduct($item['product_id']);
            
            $order_data['products'][] = array(
                'product_id' => $item['product_id'],
                'unit_id' => $item['unit_id'],
                'name' => $item['product_name'],
                'model' => $product_info ? $product_info['model'] : '',
                'quantity' => $item['quantity'],
                'price' => $item['price'],
                'total' => $item['total'],
                'tax' => $this->getTaxRateForProduct($item['product_id']),
                'reward' => 0
            );
        }
        
        // Add order totals
        $order_data['totals'] = array();
        
        $order_data['totals'][] = array(
            'code' => 'sub_total',
            'title' => 'المجموع الفرعي',
            'value' => $quote_info['total_amount'],
            'sort_order' => 1
        );
        
        if ($quote_info['discount_amount'] > 0) {
            $order_data['totals'][] = array(
                'code' => 'discount',
                'title' => 'الخصم',
                'value' => -$quote_info['discount_amount'],
                'sort_order' => 2
            );
        }
        
        if ($quote_info['tax_amount'] > 0) {
            $order_data['totals'][] = array(
                'code' => 'tax',
                'title' => 'الضريبة',
                'value' => $quote_info['tax_amount'],
                'sort_order' => 3
            );
        }
        
        $order_data['totals'][] = array(
            'code' => 'total',
            'title' => 'الإجمالي',
            'value' => $quote_info['net_amount'],
            'sort_order' => 9
        );
        
        // Create the order
        $order_id = $this->model_sale_order->addOrder($order_data);
        
        if ($order_id) {
            // Update quote with order reference
            $this->db->query("UPDATE " . DB_PREFIX . "sales_quotation SET 
                converted_to_order = '1',
                order_id = '" . (int)$order_id . "' 
                WHERE quotation_id = '" . (int)$quote_id . "'");
            
            return $order_id;
        }
        
        return false;
    }

    public function generateQuoteNumber() {
        $prefix = 'Q-' . date('Ym') . '-';
        
        $query = $this->db->query("SELECT MAX(CAST(SUBSTRING(quotation_number, " . (strlen($prefix) + 1) . ") AS UNSIGNED)) AS max_number 
            FROM " . DB_PREFIX . "sales_quotation 
            WHERE quotation_number LIKE '" . $this->db->escape($prefix) . "%'");
        
        $max_number = $query->row['max_number'];
        
        if ($max_number) {
            $next_number = $max_number + 1;
        } else {
            $next_number = 1;
        }
        
        return $prefix . str_pad($next_number, 4, '0', STR_PAD_LEFT);
    }

    protected function getCustomerGroupId($customer_id) {
        $query = $this->db->query("SELECT customer_group_id FROM " . DB_PREFIX . "customer 
            WHERE customer_id = '" . (int)$customer_id . "'");
        
        return $query->row ? $query->row['customer_group_id'] : 0;
    }

    protected function getCustomerFirstname($customer_id) {
        $query = $this->db->query("SELECT firstname FROM " . DB_PREFIX . "customer 
            WHERE customer_id = '" . (int)$customer_id . "'");
        
        return $query->row ? $query->row['firstname'] : '';
    }

    protected function getCustomerLastname($customer_id) {
        $query = $this->db->query("SELECT lastname FROM " . DB_PREFIX . "customer 
            WHERE customer_id = '" . (int)$customer_id . "'");
        
        return $query->row ? $query->row['lastname'] : '';
    }

    protected function getCustomerEmail($customer_id) {
        $query = $this->db->query("SELECT email FROM " . DB_PREFIX . "customer 
            WHERE customer_id = '" . (int)$customer_id . "'");
        
        return $query->row ? $query->row['email'] : '';
    }

    protected function getCustomerTelephone($customer_id) {
        $query = $this->db->query("SELECT telephone FROM " . DB_PREFIX . "customer 
            WHERE customer_id = '" . (int)$customer_id . "'");
        
        return $query->row ? $query->row['telephone'] : '';
    }

    protected function getCustomerAddress($customer_id) {
        $query = $this->db->query("SELECT a.*, c.name AS country, z.name AS zone 
            FROM " . DB_PREFIX . "address a 
            LEFT JOIN " . DB_PREFIX . "country c ON (a.country_id = c.country_id) 
            LEFT JOIN " . DB_PREFIX . "zone z ON (a.zone_id = z.zone_id) 
            WHERE a.customer_id = '" . (int)$customer_id . "' 
            LIMIT 1");
        
        return $query->row;
    }

    protected function getTaxRateForProduct($product_id) {
        $this->load->model('catalog/product');
        $product_info = $this->model_catalog_product->getProduct($product_id);
        
        if ($product_info && $product_info['tax_class_id']) {
            $this->load->model('localisation/tax_class');
            $tax_rates = $this->model_localisation_tax_class->getTaxRates($product_info['tax_class_id']);
            
            if ($tax_rates) {
                return $tax_rates[0]['rate'];
            }
        }
        
        return 0;
    }
}