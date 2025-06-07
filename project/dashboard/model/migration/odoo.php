<?php
namespace Opencart\Admin\Model\Migration;

class Odoo extends \Opencart\System\Engine\Model {
    private $apiUrl;
    private $database;
    private $username;
    private $password;

    public function processOdooImport($data) {
        try {
            $this->apiUrl = rtrim($data['server_url'], '/') . '/jsonrpc';
            $this->database = $data['database'];
            $this->username = $data['username'];
            $this->password = $data['password'];

            // Authenticate and get user context
            $uid = $this->authenticate();
            if (!$uid) {
                throw new \Exception('Authentication failed');
            }

            // Import core data
            $products = $this->fetchProducts($uid);
            $customers = $this->fetchCustomers($uid);
            $suppliers = $this->fetchSuppliers($uid);
            $orders = $this->fetchOrders($uid);
            $invoices = $this->fetchInvoices($uid);

            return [
                'success' => true,
                'total_records' => count($products) + count($customers) + count($suppliers) + count($orders) + count($invoices),
                'data' => [
                    'product' => $products,
                    'customer' => $customers,
                    'supplier' => $suppliers,
                    'order' => $orders,
                    'invoice' => $invoices
                ]
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    private function authenticate() {
        $response = $this->makeRequest('common', 'authenticate', [
            $this->database,
            $this->username,
            $this->password,
            []
        ]);

        return $response;
    }

    private function fetchProducts($uid) {
        $fields = [
            'name', 'default_code', 'list_price', 'standard_price', 'type', 
            'categ_id', 'description', 'qty_available', 'weight', 'volume',
            'sale_ok', 'purchase_ok', 'active', 'image_1920', 'barcode',
            'taxes_id', 'supplier_taxes_id', 'property_stock_production',
            'property_stock_inventory', 'tracking', 'detailed_type'
        ];
        return $this->search('product.product', $uid, [['active', '=', true]], $fields);
    }

    private function fetchCustomers($uid) {
        $fields = [
            'name', 'email', 'phone', 'mobile', 'street', 'street2', 'city', 
            'zip', 'country_id', 'state_id', 'vat', 'website', 'comment',
            'customer_rank', 'credit_limit', 'property_payment_term_id',
            'property_product_pricelist', 'ref', 'lang', 'active'
        ];
        return $this->search('res.partner', $uid, [['customer_rank', '>', 0], ['active', '=', true]], $fields);
    }

    private function fetchSuppliers($uid) {
        $fields = ['name', 'email', 'phone', 'street', 'city', 'zip', 'country_id', 'vat'];
        return $this->search('res.partner', $uid, [['supplier_rank', '>', 0]], $fields);
    }

    private function fetchOrders($uid) {
        $fields = [
            'name', 'partner_id', 'partner_invoice_id', 'partner_shipping_id',
            'date_order', 'amount_total', 'amount_untaxed', 'amount_tax',
            'state', 'order_line', 'payment_term_id', 'user_id', 'team_id',
            'client_order_ref', 'origin', 'currency_id', 'campaign_id',
            'medium_id', 'source_id', 'note', 'fiscal_position_id'
        ];
        return $this->search('sale.order', $uid, [], $fields);
    }

    private function fetchInvoices($uid) {
        $fields = [
            'name', 'partner_id', 'invoice_date', 'invoice_date_due', 'amount_total',
            'amount_untaxed', 'amount_tax', 'state', 'invoice_line_ids', 'currency_id',
            'move_type', 'payment_state', 'payment_reference', 'journal_id',
            'fiscal_position_id', 'invoice_payment_term_id', 'narration',
            'payment_reference', 'ref', 'posted_before', 'move_type'
        ];
        return $this->search('account.move', $uid, [['move_type', 'in', ['out_invoice', 'out_refund', 'in_invoice', 'in_refund']]], $fields);
    }

    private function search($model, $uid, $domain = [], $fields = []) {
        $offset = 0;
        $limit = 100;
        $all_records = [];

        do {
            $records = $this->makeRequest('object', 'execute_kw', [
                $this->database,
                $uid,
                $this->password,
                $model,
                'search_read',
                [$domain],
                [
                    'fields' => $fields,
                    'offset' => $offset,
                    'limit' => $limit
                ]
            ]);

            if (!empty($records)) {
                $all_records = array_merge($all_records, $records);
                $offset += $limit;
            }
        } while (!empty($records) && count($records) == $limit);

        return $all_records;
    }

    private function makeRequest($service, $method, $params) {
        $data = [
            'jsonrpc' => '2.0',
            'method' => $method,
            'params' => $params,
            'id' => mt_rand(1, 999999999)
        ];

        $ch = curl_init($this->apiUrl . '/' . $service);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Accept: application/json'
            ]
        ]);

        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new \Exception('API Request Error: ' . $error);
        }

        $result = json_decode($response, true);
        if (isset($result['error'])) {
            throw new \Exception($result['error']['message']);
        }

        return $result['result'];
    }
}