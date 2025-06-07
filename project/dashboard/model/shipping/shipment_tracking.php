<?php
/**
 * نموذج نظام تتبع الشحنات المتقدم
 * 
 * يوفر تتبع شامل للشحنات مع:
 * - تتبع الشحنات في الوقت الفعلي
 * - تحديث الحالات التلقائي من APIs
 * - إشعارات العملاء التلقائية
 * - تقارير الأداء والتسليم
 * - التكامل مع أرامكس وبوسطة
 * 
 * @author ERP Team
 * @version 1.0
 * @since 2024
 */

class ModelShippingShipmentTracking extends Model {
    
    /**
     * تحديث حالات الشحنات من APIs شركات الشحن
     */
    public function updateShipmentsFromAPI() {
        // الحصول على الشحنات النشطة
        $active_shipments = $this->getActiveShipments();
        
        $updated_count = 0;
        $errors = [];
        
        foreach ($active_shipments as $shipment) {
            try {
                $tracking_data = $this->getTrackingFromAPI($shipment);
                
                if ($tracking_data) {
                    $this->updateShipmentStatus($shipment['shipping_order_id'], $tracking_data);
                    $updated_count++;
                }
                
            } catch (Exception $e) {
                $errors[] = 'Shipment ' . $shipment['tracking_number'] . ': ' . $e->getMessage();
            }
        }
        
        return [
            'updated_count' => $updated_count,
            'total_shipments' => count($active_shipments),
            'errors' => $errors
        ];
    }
    
    /**
     * الحصول على الشحنات النشطة
     */
    private function getActiveShipments() {
        $query = $this->db->query("
            SELECT so.*, sc.code as company_code, sc.name as company_name
            FROM cod_shipping_order so
            LEFT JOIN cod_shipping_company sc ON (so.company_id = sc.company_id)
            WHERE so.status IN ('processed', 'shipped', 'in_transit')
            AND so.tracking_number IS NOT NULL
            AND so.tracking_number != ''
            ORDER BY so.created_at DESC
        ");
        
        return $query->rows;
    }
    
    /**
     * الحصول على بيانات التتبع من API شركة الشحن
     */
    private function getTrackingFromAPI($shipment) {
        switch ($shipment['company_code']) {
            case 'aramex':
                return $this->getAramexTracking($shipment);
            case 'bosta':
                return $this->getBostaTracking($shipment);
            default:
                throw new Exception('شركة الشحن غير مدعومة للتتبع');
        }
    }
    
    /**
     * الحصول على بيانات التتبع من أرامكس
     */
    private function getAramexTracking($shipment) {
        $this->load->model('shipping/shipping_integration');
        
        // تحميل إعدادات أرامكس
        $config = $this->getShippingCompanyConfig($shipment['company_id']);
        
        $tracking_data = [
            'ClientInfo' => [
                'UserName' => $config['username'],
                'Password' => $config['password'],
                'Version' => 'v1.0',
                'AccountNumber' => $config['account_number'],
                'AccountPin' => $config['account_pin'],
                'AccountEntity' => $config['account_entity'],
                'AccountCountryCode' => $config['account_country_code']
            ],
            'Transaction' => [
                'Reference1' => '',
                'Reference2' => '',
                'Reference3' => '',
                'Reference4' => '',
                'Reference5' => ''
            ],
            'Shipments' => [
                $shipment['tracking_number']
            ]
        ];
        
        try {
            $wsdl = $config['api_url'] . '/ShippingAPI.V2/Tracking/Service_1_0.svc?wsdl';
            
            $soap_client = new SoapClient($wsdl, [
                'trace' => true,
                'exceptions' => true,
                'cache_wsdl' => WSDL_CACHE_NONE
            ]);
            
            $response = $soap_client->TrackShipments($tracking_data);
            
            if ($response->HasErrors) {
                throw new Exception('Aramex Tracking API Error');
            }
            
            return $this->parseAramexTrackingResponse($response);
            
        } catch (Exception $e) {
            throw new Exception('Aramex Tracking Error: ' . $e->getMessage());
        }
    }
    
    /**
     * الحصول على بيانات التتبع من بوسطة
     */
    private function getBostaTracking($shipment) {
        $config = $this->getShippingCompanyConfig($shipment['company_id']);
        
        $url = $config['api_url'] . '/api/v2/deliveries/' . $shipment['tracking_number'];
        
        $headers = [
            'Authorization: Bearer ' . $config['api_key'],
            'Content-Type: application/json'
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($http_code !== 200) {
            throw new Exception('Bosta Tracking API Error: HTTP ' . $http_code);
        }
        
        $response_data = json_decode($response, true);
        
        if (!$response_data) {
            throw new Exception('Invalid Bosta API Response');
        }
        
        return $this->parseBostaTrackingResponse($response_data);
    }
    
    /**
     * تحليل استجابة تتبع أرامكس
     */
    private function parseAramexTrackingResponse($response) {
        $tracking_events = [];
        
        if (isset($response->TrackingResults) && count($response->TrackingResults) > 0) {
            $result = $response->TrackingResults[0];
            
            if (isset($result->UpdatesDetails) && count($result->UpdatesDetails) > 0) {
                foreach ($result->UpdatesDetails as $update) {
                    $tracking_events[] = [
                        'status' => $this->mapAramexStatus($update->UpdateCode),
                        'status_details' => $update->UpdateDescription,
                        'location' => $update->UpdateLocation,
                        'tracking_date' => $this->parseAramexDate($update->UpdateDateTime),
                        'agent_name' => isset($update->Comments) ? $update->Comments : null
                    ];
                }
            }
        }
        
        return $tracking_events;
    }
    
    /**
     * تحليل استجابة تتبع بوسطة
     */
    private function parseBostaTrackingResponse($response) {
        $tracking_events = [];
        
        if (isset($response['timeline']) && is_array($response['timeline'])) {
            foreach ($response['timeline'] as $event) {
                $tracking_events[] = [
                    'status' => $this->mapBostaStatus($event['state']),
                    'status_details' => $event['description'],
                    'location' => isset($event['hub']) ? $event['hub'] : null,
                    'tracking_date' => date('Y-m-d H:i:s', strtotime($event['timestamp'])),
                    'agent_name' => null
                ];
            }
        }
        
        return $tracking_events;
    }
    
    /**
     * تحديث حالة الشحنة
     */
    private function updateShipmentStatus($shipping_order_id, $tracking_events) {
        if (empty($tracking_events)) {
            return;
        }
        
        // الحصول على آخر حدث
        $latest_event = $tracking_events[0];
        
        // تحديث حالة أمر الشحن
        $this->db->query("
            UPDATE cod_shipping_order SET 
            status = '" . $this->db->escape($latest_event['status']) . "',
            updated_at = NOW()
            WHERE shipping_order_id = '" . (int)$shipping_order_id . "'
        ");
        
        // إضافة أحداث التتبع الجديدة
        foreach ($tracking_events as $event) {
            $this->addTrackingEvent($shipping_order_id, $event);
        }
        
        // تحديث حالة الطلب الأصلي
        $this->updateOrderStatusFromShipment($shipping_order_id, $latest_event['status']);
        
        // إرسال إشعار للعميل إذا لزم الأمر
        $this->sendCustomerNotification($shipping_order_id, $latest_event);
    }
    
    /**
     * إضافة حدث تتبع
     */
    private function addTrackingEvent($shipping_order_id, $event) {
        // التحقق من عدم وجود الحدث مسبقاً
        $existing_query = $this->db->query("
            SELECT tracking_id FROM cod_shipping_tracking 
            WHERE shipping_order_id = '" . (int)$shipping_order_id . "'
            AND status = '" . $this->db->escape($event['status']) . "'
            AND tracking_date = '" . $this->db->escape($event['tracking_date']) . "'
        ");
        
        if ($existing_query->num_rows == 0) {
            $this->db->query("
                INSERT INTO cod_shipping_tracking SET 
                shipping_order_id = '" . (int)$shipping_order_id . "',
                status = '" . $this->db->escape($event['status']) . "',
                status_details = '" . $this->db->escape($event['status_details']) . "',
                tracking_date = '" . $this->db->escape($event['tracking_date']) . "',
                location = '" . $this->db->escape($event['location']) . "',
                agent_name = '" . $this->db->escape($event['agent_name']) . "',
                source = 'api',
                created_at = NOW()
            ");
        }
    }
    
    /**
     * تحديث حالة الطلب الأصلي
     */
    private function updateOrderStatusFromShipment($shipping_order_id, $shipment_status) {
        // الحصول على معرف الطلب
        $order_query = $this->db->query("
            SELECT order_id FROM cod_shipping_order 
            WHERE shipping_order_id = '" . (int)$shipping_order_id . "'
        ");
        
        if ($order_query->num_rows) {
            $order_id = $order_query->row['order_id'];
            
            // تحديد حالة الطلب بناءً على حالة الشحنة
            $order_status_mapping = [
                'shipped' => 17,      // Shipped
                'in_transit' => 17,   // Shipped
                'delivered' => 5,     // Complete
                'returned' => 11,     // Returned
                'failed' => 10        // Failed
            ];
            
            if (isset($order_status_mapping[$shipment_status])) {
                $order_status_id = $order_status_mapping[$shipment_status];
                
                $this->db->query("
                    UPDATE cod_order SET 
                    order_status_id = '" . (int)$order_status_id . "'
                    WHERE order_id = '" . (int)$order_id . "'
                ");
                
                // إضافة سجل في تاريخ الطلب
                $this->db->query("
                    INSERT INTO cod_order_history SET 
                    order_id = '" . (int)$order_id . "',
                    order_status_id = '" . (int)$order_status_id . "',
                    comment = 'تحديث تلقائي من تتبع الشحنة: " . $shipment_status . "',
                    date_added = NOW()
                ");
            }
        }
    }
    
    /**
     * إرسال إشعار للعميل
     */
    private function sendCustomerNotification($shipping_order_id, $event) {
        // الحصول على بيانات الطلب والعميل
        $query = $this->db->query("
            SELECT o.order_id, o.email, o.firstname, o.lastname, 
                so.tracking_number, sc.name as company_name
            FROM cod_shipping_order so
            LEFT JOIN cod_order o ON (so.order_id = o.order_id)
            LEFT JOIN cod_shipping_company sc ON (so.company_id = sc.company_id)
            WHERE so.shipping_order_id = '" . (int)$shipping_order_id . "'
        ");
        
        if ($query->num_rows) {
            $order_data = $query->row;
            
            // إرسال إشعار للحالات المهمة فقط
            $important_statuses = ['shipped', 'delivered', 'returned', 'failed'];
            
            if (in_array($event['status'], $important_statuses)) {
                $this->sendTrackingEmail($order_data, $event);
                
                // يمكن إضافة إشعارات SMS هنا
                $this->sendTrackingSMS($order_data, $event);
            }
        }
    }
    
    /**
     * إرسال بريد إلكتروني للتتبع
     */
    private function sendTrackingEmail($order_data, $event) {
        $this->load->model('setting/mail');
        
        $subject = 'تحديث حالة الشحنة - طلب رقم ' . $order_data['order_id'];
        
        $message = "عزيزي/عزيزتي " . $order_data['firstname'] . " " . $order_data['lastname'] . ",\n\n";
        $message .= "تم تحديث حالة شحنتك:\n\n";
        $message .= "رقم الطلب: " . $order_data['order_id'] . "\n";
        $message .= "رقم التتبع: " . $order_data['tracking_number'] . "\n";
        $message .= "شركة الشحن: " . $order_data['company_name'] . "\n";
        $message .= "الحالة الحالية: " . $this->getStatusText($event['status']) . "\n";
        $message .= "تفاصيل الحالة: " . $event['status_details'] . "\n";
        
        if ($event['location']) {
            $message .= "الموقع: " . $event['location'] . "\n";
        }
        
        $message .= "\nشكراً لتسوقكم معنا.";
        
        // إرسال البريد الإلكتروني
        $this->model_setting_mail->send($order_data['email'], $subject, $message);
    }
    
    /**
     * إرسال رسالة SMS للتتبع
     */
    private function sendTrackingSMS($order_data, $event) {
        // يمكن تطوير هذه الدالة لإرسال SMS
        // باستخدام خدمات SMS المحلية
    }
    
    /**
     * الحصول على إعدادات شركة الشحن
     */
    private function getShippingCompanyConfig($company_id) {
        $query = $this->db->query("
            SELECT `key`, `value` FROM cod_shipping_company_config 
            WHERE company_id = '" . (int)$company_id . "'
            AND environment = 'production'
        ");
        
        $config = [];
        foreach ($query->rows as $row) {
            $config[$row['key']] = $row['value'];
        }
        
        return $config;
    }
    
    /**
     * تحويل حالة أرامكس
     */
    private function mapAramexStatus($aramex_code) {
        $status_mapping = [
            'SH001' => 'shipped',
            'SH002' => 'in_transit',
            'SH003' => 'delivered',
            'SH004' => 'returned',
            'SH005' => 'failed'
        ];
        
        return isset($status_mapping[$aramex_code]) ? $status_mapping[$aramex_code] : 'in_transit';
    }
    
    /**
     * تحويل حالة بوسطة
     */
    private function mapBostaStatus($bosta_state) {
        $status_mapping = [
            'DELIVERED_TO_CUSTOMER' => 'delivered',
            'DELIVERED_TO_BUSINESS' => 'delivered',
            'PICKED_UP' => 'shipped',
            'RECEIVED_AT_WAREHOUSE' => 'in_transit',
            'OUT_FOR_DELIVERY' => 'in_transit',
            'DELIVERY_FAILED' => 'failed',
            'RETURNED_TO_BUSINESS' => 'returned'
        ];
        
        return isset($status_mapping[$bosta_state]) ? $status_mapping[$bosta_state] : 'in_transit';
    }
    
    /**
     * تحليل تاريخ أرامكس
     */
    private function parseAramexDate($aramex_date) {
        // تحليل تاريخ أرامكس من صيغة /Date(timestamp)/
        if (preg_match('/\/Date\((\d+)\)\//', $aramex_date, $matches)) {
            return date('Y-m-d H:i:s', $matches[1] / 1000);
        }
        
        return date('Y-m-d H:i:s');
    }
    
    /**
     * الحصول على نص الحالة
     */
    private function getStatusText($status) {
        $status_texts = [
            'pending' => 'في الانتظار',
            'processed' => 'تم المعالجة',
            'shipped' => 'تم الشحن',
            'in_transit' => 'في الطريق',
            'delivered' => 'تم التسليم',
            'returned' => 'تم الإرجاع',
            'failed' => 'فشل التسليم',
            'cancelled' => 'ملغي'
        ];
        
        return isset($status_texts[$status]) ? $status_texts[$status] : $status;
    }
    
    /**
     * الحصول على تفاصيل تتبع الشحنة
     */
    public function getShipmentTrackingDetails($shipping_order_id) {
        // الحصول على بيانات أمر الشحن
        $shipment_query = $this->db->query("
            SELECT so.*, o.order_id, o.firstname, o.lastname, o.email,
                sc.name as company_name, sc.code as company_code
            FROM cod_shipping_order so
            LEFT JOIN cod_order o ON (so.order_id = o.order_id)
            LEFT JOIN cod_shipping_company sc ON (so.company_id = sc.company_id)
            WHERE so.shipping_order_id = '" . (int)$shipping_order_id . "'
        ");
        
        if (!$shipment_query->num_rows) {
            return false;
        }
        
        $shipment = $shipment_query->row;
        
        // الحصول على أحداث التتبع
        $tracking_query = $this->db->query("
            SELECT * FROM cod_shipping_tracking 
            WHERE shipping_order_id = '" . (int)$shipping_order_id . "'
            ORDER BY tracking_date DESC
        ");
        
        $shipment['tracking_events'] = $tracking_query->rows;
        
        return $shipment;
    }
}
