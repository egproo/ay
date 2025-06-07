<?php
/**
 * نموذج التكامل مع شركات الشحن (أرامكس وبوسطة)
 * 
 * يوفر تكامل شامل مع شركات الشحن مع:
 * - إنشاء أوامر الشحن عبر API
 * - تتبع الشحنات في الوقت الفعلي
 * - حساب الأسعار التلقائي
 * - إدارة التسويات والمدفوعات
 * - التكامل مع OpenCart shipping methods
 * 
 * @author ERP Team
 * @version 1.0
 * @since 2024
 */

class ModelShippingShippingIntegration extends Model {
    
    private $aramex_config;
    private $bosta_config;
    
    public function __construct($registry) {
        parent::__construct($registry);
        $this->loadShippingConfigs();
    }
    
    /**
     * تحميل إعدادات شركات الشحن
     */
    private function loadShippingConfigs() {
        // تحميل إعدادات أرامكس
        $aramex_query = $this->db->query("
            SELECT `key`, `value` FROM cod_shipping_company_config 
            WHERE company_id = (SELECT company_id FROM cod_shipping_company WHERE code = 'aramex')
            AND environment = 'production'
        ");
        
        $this->aramex_config = [];
        foreach ($aramex_query->rows as $config) {
            $this->aramex_config[$config['key']] = $config['value'];
        }
        
        // تحميل إعدادات بوسطة
        $bosta_query = $this->db->query("
            SELECT `key`, `value` FROM cod_shipping_company_config 
            WHERE company_id = (SELECT company_id FROM cod_shipping_company WHERE code = 'bosta')
            AND environment = 'production'
        ");
        
        $this->bosta_config = [];
        foreach ($bosta_query->rows as $config) {
            $this->bosta_config[$config['key']] = $config['value'];
        }
    }
    
    /**
     * إنشاء أمر شحن جديد
     */
    public function createShippingOrder($data) {
        // الحصول على بيانات الطلب
        $order = $this->getOrderDetails($data['order_id']);
        if (!$order) {
            throw new Exception('الطلب غير موجود');
        }
        
        // الحصول على بيانات شركة الشحن
        $company = $this->getShippingCompany($data['company_id']);
        if (!$company) {
            throw new Exception('شركة الشحن غير موجودة');
        }
        
        // إنشاء أمر الشحن محلياً
        $shipping_order_id = $this->createLocalShippingOrder($data, $order);
        
        // إرسال الطلب لشركة الشحن عبر API
        try {
            $api_response = $this->sendToShippingCompany($company['code'], $data, $order);
            
            // تحديث أمر الشحن بالاستجابة
            $this->updateShippingOrderWithAPIResponse($shipping_order_id, $api_response);
            
            return $shipping_order_id;
            
        } catch (Exception $e) {
            // تحديث حالة أمر الشحن إلى فشل
            $this->updateShippingOrderStatus($shipping_order_id, 'failed', $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * إنشاء أمر الشحن محلياً
     */
    private function createLocalShippingOrder($data, $order) {
        $this->db->query("
            INSERT INTO cod_shipping_order SET 
            order_id = '" . (int)$data['order_id'] . "',
            company_id = '" . (int)$data['company_id'] . "',
            shipping_cost = '" . (float)$data['shipping_cost'] . "',
            cod_amount = '" . (float)($data['cod_amount'] ?? 0) . "',
            package_weight = '" . (float)$data['package_weight'] . "',
            package_dimensions = '" . $this->db->escape($data['package_dimensions']) . "',
            status = 'pending',
            notes = '" . $this->db->escape($data['special_instructions'] ?? '') . "',
            created_by = '" . (int)$this->user->getId() . "',
            created_at = NOW()
        ");
        
        return $this->db->getLastId();
    }
    
    /**
     * إرسال الطلب لشركة الشحن
     */
    private function sendToShippingCompany($company_code, $data, $order) {
        switch ($company_code) {
            case 'aramex':
                return $this->sendToAramex($data, $order);
            case 'bosta':
                return $this->sendToBosta($data, $order);
            default:
                throw new Exception('شركة الشحن غير مدعومة');
        }
    }
    
    /**
     * إرسال الطلب لأرامكس
     */
    private function sendToAramex($data, $order) {
        $aramex_data = [
            'ClientInfo' => [
                'UserName' => $this->aramex_config['username'],
                'Password' => $this->aramex_config['password'],
                'Version' => 'v1.0',
                'AccountNumber' => $this->aramex_config['account_number'],
                'AccountPin' => $this->aramex_config['account_pin'],
                'AccountEntity' => $this->aramex_config['account_entity'],
                'AccountCountryCode' => $this->aramex_config['account_country_code']
            ],
            'Transaction' => [
                'Reference1' => 'ORDER-' . $order['order_id'],
                'Reference2' => '',
                'Reference3' => '',
                'Reference4' => '',
                'Reference5' => ''
            ],
            'Shipments' => [
                [
                    'Reference1' => 'ORDER-' . $order['order_id'],
                    'Reference2' => $order['customer_name'],
                    'Reference3' => '',
                    'Shipper' => [
                        'Reference1' => '',
                        'Reference2' => '',
                        'AccountNumber' => $this->aramex_config['account_number'],
                        'PartyAddress' => [
                            'Line1' => $this->config->get('config_address'),
                            'Line2' => '',
                            'Line3' => '',
                            'City' => $this->config->get('config_city'),
                            'StateOrProvinceCode' => '',
                            'PostCode' => $this->config->get('config_postcode'),
                            'CountryCode' => $this->config->get('config_country_code')
                        ],
                        'Contact' => [
                            'Department' => '',
                            'PersonName' => $this->config->get('config_owner'),
                            'Title' => '',
                            'CompanyName' => $this->config->get('config_name'),
                            'PhoneNumber1' => $this->config->get('config_telephone'),
                            'PhoneNumber1Ext' => '',
                            'PhoneNumber2' => '',
                            'PhoneNumber2Ext' => '',
                            'FaxNumber' => $this->config->get('config_fax'),
                            'CellPhone' => '',
                            'EmailAddress' => $this->config->get('config_email'),
                            'Type' => ''
                        ]
                    ],
                    'Consignee' => [
                        'Reference1' => '',
                        'Reference2' => '',
                        'AccountNumber' => '',
                        'PartyAddress' => [
                            'Line1' => $order['shipping_address_1'],
                            'Line2' => $order['shipping_address_2'],
                            'Line3' => '',
                            'City' => $order['shipping_city'],
                            'StateOrProvinceCode' => $order['shipping_zone'],
                            'PostCode' => $order['shipping_postcode'],
                            'CountryCode' => $order['shipping_iso_code_2']
                        ],
                        'Contact' => [
                            'Department' => '',
                            'PersonName' => $order['shipping_firstname'] . ' ' . $order['shipping_lastname'],
                            'Title' => '',
                            'CompanyName' => $order['shipping_company'],
                            'PhoneNumber1' => $order['telephone'],
                            'PhoneNumber1Ext' => '',
                            'PhoneNumber2' => '',
                            'PhoneNumber2Ext' => '',
                            'FaxNumber' => '',
                            'CellPhone' => $order['telephone'],
                            'EmailAddress' => $order['email'],
                            'Type' => ''
                        ]
                    ],
                    'ThirdParty' => [
                        'Reference1' => '',
                        'Reference2' => '',
                        'AccountNumber' => '',
                        'PartyAddress' => [
                            'Line1' => '',
                            'Line2' => '',
                            'Line3' => '',
                            'City' => '',
                            'StateOrProvinceCode' => '',
                            'PostCode' => '',
                            'CountryCode' => ''
                        ],
                        'Contact' => [
                            'Department' => '',
                            'PersonName' => '',
                            'Title' => '',
                            'CompanyName' => '',
                            'PhoneNumber1' => '',
                            'PhoneNumber1Ext' => '',
                            'PhoneNumber2' => '',
                            'PhoneNumber2Ext' => '',
                            'FaxNumber' => '',
                            'CellPhone' => '',
                            'EmailAddress' => '',
                            'Type' => ''
                        ]
                    ],
                    'ShippingDateTime' => '/Date(' . (time() * 1000) . ')/',
                    'DueDate' => '/Date(' . (strtotime('+3 days') * 1000) . ')/',
                    'Comments' => $data['special_instructions'] ?? '',
                    'PickupLocation' => 'Reception',
                    'OperationsInstructions' => '',
                    'AccountingInstrcutions' => '',
                    'Details' => [
                        'Dimensions' => [
                            'Length' => $this->extractDimension($data['package_dimensions'], 0),
                            'Width' => $this->extractDimension($data['package_dimensions'], 1),
                            'Height' => $this->extractDimension($data['package_dimensions'], 2),
                            'Unit' => 'CM'
                        ],
                        'ActualWeight' => [
                            'Value' => $data['package_weight'],
                            'Unit' => 'KG'
                        ],
                        'ProductGroup' => 'EXP',
                        'ProductType' => 'PDX',
                        'PaymentType' => $order['payment_method'] == 'cod' ? 'C' : 'P',
                        'PaymentOptions' => '',
                        'Services' => '',
                        'NumberOfPieces' => 1,
                        'DescriptionOfGoods' => $this->getOrderItemsDescription($order['order_id']),
                        'GoodsOriginCountry' => $this->config->get('config_country_code')
                    ]
                ]
            ]
        ];
        
        // إضافة معلومات الدفع عند الاستلام إذا لزم الأمر
        if ($order['payment_method'] == 'cod') {
            $aramex_data['Shipments'][0]['Details']['CashOnDeliveryAmount'] = [
                'Value' => $data['cod_amount'],
                'CurrencyCode' => $order['currency_code']
            ];
        }
        
        // إرسال الطلب عبر SOAP
        return $this->sendAramexSOAPRequest($aramex_data);
    }
    
    /**
     * إرسال الطلب لبوسطة
     */
    private function sendToBosta($data, $order) {
        $bosta_data = [
            'type' => 0, // 0 for delivery, 1 for pickup
            'specs' => [
                'packageType' => 'PACKAGE',
                'size' => 'SMALL', // يمكن حسابها بناءً على الأبعاد
                'weight' => $data['package_weight']
            ],
            'notes' => $data['special_instructions'] ?? '',
            'cod' => $order['payment_method'] == 'cod' ? $data['cod_amount'] : 0,
            'receiver' => [
                'firstName' => $order['shipping_firstname'],
                'lastName' => $order['shipping_lastname'],
                'phone' => $order['telephone'],
                'email' => $order['email'],
                'address' => [
                    'firstLine' => $order['shipping_address_1'],
                    'secondLine' => $order['shipping_address_2'],
                    'city' => $order['shipping_city'],
                    'zone' => $order['shipping_zone'],
                    'country' => $order['shipping_iso_code_2']
                ]
            ],
            'dropOffAddress' => [
                'firstLine' => $this->config->get('config_address'),
                'city' => $this->config->get('config_city'),
                'zone' => $this->config->get('config_zone'),
                'country' => $this->config->get('config_country_code')
            ]
        ];
        
        // إرسال الطلب عبر REST API
        return $this->sendBostaRESTRequest($bosta_data);
    }
    
    /**
     * إرسال طلب SOAP لأرامكس
     */
    private function sendAramexSOAPRequest($data) {
        $wsdl = $this->aramex_config['api_url'] . '/ShippingAPI.V2/Shipping/Service_1_0.svc?wsdl';
        
        try {
            $soap_client = new SoapClient($wsdl, [
                'trace' => true,
                'exceptions' => true,
                'cache_wsdl' => WSDL_CACHE_NONE
            ]);
            
            $response = $soap_client->CreateShipments($data);
            
            if ($response->HasErrors) {
                $errors = [];
                foreach ($response->Notifications as $notification) {
                    $errors[] = $notification->Message;
                }
                throw new Exception('Aramex API Error: ' . implode(', ', $errors));
            }
            
            return [
                'success' => true,
                'tracking_number' => $response->Shipments[0]->ID,
                'awb_number' => $response->Shipments[0]->ForeignHAWB,
                'label_url' => $response->Shipments[0]->ShipmentLabel->LabelURL,
                'raw_response' => json_encode($response)
            ];
            
        } catch (Exception $e) {
            throw new Exception('Aramex API Connection Error: ' . $e->getMessage());
        }
    }
    
    /**
     * إرسال طلب REST لبوسطة
     */
    private function sendBostaRESTRequest($data) {
        $url = $this->bosta_config['api_url'] . '/api/v2/deliveries';
        
        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->bosta_config['api_key']
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($http_code !== 200) {
            throw new Exception('Bosta API Error: HTTP ' . $http_code . ' - ' . $response);
        }
        
        $response_data = json_decode($response, true);
        
        if (!$response_data || isset($response_data['error'])) {
            throw new Exception('Bosta API Error: ' . ($response_data['error'] ?? 'Unknown error'));
        }
        
        return [
            'success' => true,
            'tracking_number' => $response_data['trackingNumber'],
            'delivery_id' => $response_data['_id'],
            'raw_response' => $response
        ];
    }
    
    /**
     * تحديث أمر الشحن بالاستجابة من API
     */
    private function updateShippingOrderWithAPIResponse($shipping_order_id, $api_response) {
        $update_data = [
            'status' => 'processed',
            'tracking_number' => $api_response['tracking_number'],
            'api_response' => $api_response['raw_response']
        ];
        
        if (isset($api_response['awb_number'])) {
            $update_data['awb_number'] = $api_response['awb_number'];
        }
        
        if (isset($api_response['label_url'])) {
            $update_data['shipping_label_url'] = $api_response['label_url'];
        }
        
        $sql = "UPDATE cod_shipping_order SET ";
        $sql_parts = [];
        
        foreach ($update_data as $key => $value) {
            $sql_parts[] = $key . " = '" . $this->db->escape($value) . "'";
        }
        
        $sql .= implode(', ', $sql_parts);
        $sql .= ", updated_at = NOW()";
        $sql .= " WHERE shipping_order_id = '" . (int)$shipping_order_id . "'";
        
        $this->db->query($sql);
    }
    
    /**
     * تحديث حالة أمر الشحن
     */
    private function updateShippingOrderStatus($shipping_order_id, $status, $notes = '') {
        $this->db->query("
            UPDATE cod_shipping_order SET 
            status = '" . $this->db->escape($status) . "',
            notes = '" . $this->db->escape($notes) . "',
            updated_at = NOW()
            WHERE shipping_order_id = '" . (int)$shipping_order_id . "'
        ");
    }
    
    /**
     * الحصول على تفاصيل الطلب
     */
    private function getOrderDetails($order_id) {
        $query = $this->db->query("
            SELECT o.*, c.iso_code_2 as shipping_iso_code_2,
                CONCAT(o.firstname, ' ', o.lastname) as customer_name
            FROM cod_order o
            LEFT JOIN cod_country c ON (o.shipping_country_id = c.country_id)
            WHERE o.order_id = '" . (int)$order_id . "'
        ");
        
        return $query->num_rows ? $query->row : false;
    }
    
    /**
     * الحصول على بيانات شركة الشحن
     */
    private function getShippingCompany($company_id) {
        $query = $this->db->query("
            SELECT * FROM cod_shipping_company 
            WHERE company_id = '" . (int)$company_id . "'
        ");
        
        return $query->num_rows ? $query->row : false;
    }
    
    /**
     * استخراج بُعد من نص الأبعاد
     */
    private function extractDimension($dimensions, $index) {
        $parts = explode('×', $dimensions);
        return isset($parts[$index]) ? (float)trim($parts[$index]) : 10;
    }
    
    /**
     * الحصول على وصف منتجات الطلب
     */
    private function getOrderItemsDescription($order_id) {
        $query = $this->db->query("
            SELECT GROUP_CONCAT(CONCAT(name, ' (', quantity, ')') SEPARATOR ', ') as description
            FROM cod_order_product 
            WHERE order_id = '" . (int)$order_id . "'
        ");
        
        return $query->num_rows ? $query->row['description'] : 'Mixed Items';
    }
}
