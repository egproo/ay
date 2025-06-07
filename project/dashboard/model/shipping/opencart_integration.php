<?php
/**
 * نموذج التكامل مع طرق الشحن في OpenCart
 * 
 * يوفر تكامل شامل مع طرق الشحن الموجودة في OpenCart مع:
 * - ربط طرق الشحن بنظام التجهيز
 * - دعم الإضافات الخارجية
 * - حساب الأسعار التلقائي
 * - إدارة المناطق والتغطية
 * - التكامل مع المناديب الداخليين
 * 
 * @author ERP Team
 * @version 1.0
 * @since 2024
 */

class ModelShippingOpencartIntegration extends Model {
    
    /**
     * الحصول على طرق الشحن المتاحة من OpenCart
     */
    public function getOpenCartShippingMethods($order_data) {
        $this->load->model('setting/extension');
        
        $shipping_methods = [];
        
        // الحصول على طرق الشحن المفعلة
        $extensions = $this->model_setting_extension->getExtensions('shipping');
        
        foreach ($extensions as $extension) {
            if ($this->config->get('shipping_' . $extension['code'] . '_status')) {
                try {
                    $this->load->model('extension/shipping/' . $extension['code']);
                    
                    $method_class = 'model_extension_shipping_' . $extension['code'];
                    
                    if (method_exists($this->$method_class, 'getQuote')) {
                        $quote = $this->$method_class->getQuote($order_data);
                        
                        if ($quote && isset($quote['quote'])) {
                            foreach ($quote['quote'] as $option_code => $option) {
                                $shipping_methods[] = [
                                    'extension_code' => $extension['code'],
                                    'method_code' => $option_code,
                                    'title' => $quote['title'],
                                    'option_title' => $option['title'],
                                    'cost' => $option['cost'],
                                    'tax_class_id' => $option['tax_class_id'] ?? 0,
                                    'text' => $option['text'],
                                    'sort_order' => $quote['sort_order'] ?? 0,
                                    'is_internal' => $this->isInternalShippingMethod($extension['code']),
                                    'supports_tracking' => $this->supportsTracking($extension['code']),
                                    'supports_cod' => $this->supportsCOD($extension['code'])
                                ];
                            }
                        }
                    }
                } catch (Exception $e) {
                    // تسجيل الخطأ وتجاهل هذه الطريقة
                    error_log('Error loading shipping method ' . $extension['code'] . ': ' . $e->getMessage());
                }
            }
        }
        
        // إضافة المناديب الداخليين
        $internal_couriers = $this->getInternalCouriersForOrder($order_data);
        foreach ($internal_couriers as $courier) {
            $shipping_methods[] = [
                'extension_code' => 'internal_courier',
                'method_code' => 'courier_' . $courier['courier_id'],
                'title' => 'مندوب داخلي',
                'option_title' => $courier['name'],
                'cost' => $courier['delivery_fee'],
                'tax_class_id' => 0,
                'text' => $this->currency->format($courier['delivery_fee'], $this->config->get('config_currency')),
                'sort_order' => 1,
                'is_internal' => true,
                'supports_tracking' => true,
                'supports_cod' => true,
                'courier_data' => $courier
            ];
        }
        
        // ترتيب طرق الشحن حسب التكلفة
        usort($shipping_methods, function($a, $b) {
            return $a['cost'] <=> $b['cost'];
        });
        
        return $shipping_methods;
    }
    
    /**
     * إنشاء أمر شحن من طريقة OpenCart
     */
    public function createShippingOrderFromMethod($order_id, $shipping_method, $fulfillment_data) {
        // التحقق من نوع طريقة الشحن
        if ($shipping_method['extension_code'] == 'internal_courier') {
            return $this->createInternalCourierOrder($order_id, $shipping_method, $fulfillment_data);
        } else {
            return $this->createExternalShippingOrder($order_id, $shipping_method, $fulfillment_data);
        }
    }
    
    /**
     * إنشاء أمر شحن للمندوب الداخلي
     */
    private function createInternalCourierOrder($order_id, $shipping_method, $fulfillment_data) {
        $courier_id = str_replace('courier_', '', $shipping_method['method_code']);
        
        $this->load->model('shipping/internal_courier');
        
        // تعيين الطلب للمندوب
        $assignment_id = $this->model_shipping_internal_courier->assignOrderToCourier(
            $order_id, 
            $courier_id, 
            [
                'priority' => $fulfillment_data['priority'] ?? 'normal',
                'estimated_delivery_time' => $fulfillment_data['estimated_delivery_time'] ?? '',
                'special_instructions' => $fulfillment_data['special_instructions'] ?? '',
                'delivery_fee' => $shipping_method['cost']
            ]
        );
        
        // إنشاء سجل في جدول أوامر الشحن
        $this->db->query("
            INSERT INTO cod_shipping_order SET 
            order_id = '" . (int)$order_id . "',
            shipping_method = 'internal_courier',
            shipping_code = '" . $this->db->escape($shipping_method['method_code']) . "',
            shipping_cost = '" . (float)$shipping_method['cost'] . "',
            courier_id = '" . (int)$courier_id . "',
            assignment_id = '" . (int)$assignment_id . "',
            status = 'assigned',
            created_by = '" . (int)$this->user->getId() . "',
            created_at = NOW()
        ");
        
        return $this->db->getLastId();
    }
    
    /**
     * إنشاء أمر شحن خارجي
     */
    private function createExternalShippingOrder($order_id, $shipping_method, $fulfillment_data) {
        // البحث عن شركة الشحن المقابلة
        $company_query = $this->db->query("
            SELECT company_id FROM cod_shipping_company 
            WHERE opencart_extension = '" . $this->db->escape($shipping_method['extension_code']) . "'
            LIMIT 1
        ");
        
        $company_id = null;
        if ($company_query->num_rows) {
            $company_id = $company_query->row['company_id'];
        } else {
            // إنشاء شركة شحن جديدة تلقائياً
            $company_id = $this->createShippingCompanyFromExtension($shipping_method);
        }
        
        // إنشاء أمر الشحن
        $this->db->query("
            INSERT INTO cod_shipping_order SET 
            order_id = '" . (int)$order_id . "',
            company_id = '" . (int)$company_id . "',
            shipping_method = '" . $this->db->escape($shipping_method['extension_code']) . "',
            shipping_code = '" . $this->db->escape($shipping_method['method_code']) . "',
            shipping_cost = '" . (float)$shipping_method['cost'] . "',
            cod_amount = '" . (float)($fulfillment_data['cod_amount'] ?? 0) . "',
            package_weight = '" . (float)$fulfillment_data['package_weight'] . "',
            package_dimensions = '" . $this->db->escape($fulfillment_data['package_dimensions']) . "',
            status = 'pending',
            notes = '" . $this->db->escape($fulfillment_data['special_instructions'] ?? '') . "',
            created_by = '" . (int)$this->user->getId() . "',
            created_at = NOW()
        ");
        
        $shipping_order_id = $this->db->getLastId();
        
        // محاولة إرسال الطلب للشركة إذا كانت تدعم API
        if ($this->supportsAPI($shipping_method['extension_code'])) {
            try {
                $this->load->model('shipping/shipping_integration');
                $this->model_shipping_shipping_integration->createShippingOrder([
                    'order_id' => $order_id,
                    'company_id' => $company_id,
                    'shipping_cost' => $shipping_method['cost'],
                    'cod_amount' => $fulfillment_data['cod_amount'] ?? 0,
                    'package_weight' => $fulfillment_data['package_weight'],
                    'package_dimensions' => $fulfillment_data['package_dimensions'],
                    'special_instructions' => $fulfillment_data['special_instructions'] ?? ''
                ]);
            } catch (Exception $e) {
                // تسجيل الخطأ ولكن لا نوقف العملية
                error_log('Failed to create API shipping order: ' . $e->getMessage());
            }
        }
        
        return $shipping_order_id;
    }
    
    /**
     * إنشاء شركة شحن من إضافة OpenCart
     */
    private function createShippingCompanyFromExtension($shipping_method) {
        $this->db->query("
            INSERT INTO cod_shipping_company SET 
            name = '" . $this->db->escape($shipping_method['title']) . "',
            code = '" . $this->db->escape($shipping_method['extension_code']) . "',
            opencart_extension = '" . $this->db->escape($shipping_method['extension_code']) . "',
            type = 'opencart_extension',
            status = 'active',
            supports_tracking = '" . ($this->supportsTracking($shipping_method['extension_code']) ? 1 : 0) . "',
            supports_cod = '" . ($this->supportsCOD($shipping_method['extension_code']) ? 1 : 0) . "',
            created_by = '" . (int)$this->user->getId() . "',
            created_at = NOW()
        ");
        
        return $this->db->getLastId();
    }
    
    /**
     * الحصول على المناديب الداخليين المتاحين للطلب
     */
    private function getInternalCouriersForOrder($order_data) {
        $this->load->model('shipping/internal_courier');
        
        // تحديد المنطقة من بيانات الطلب
        $area_type = 'city';
        $area_value = $order_data['shipping_city'] ?? '';
        
        if (empty($area_value)) {
            $area_type = 'zone';
            $area_value = $order_data['shipping_zone'] ?? '';
        }
        
        return $this->model_shipping_internal_courier->getAvailableCouriersForArea($area_type, $area_value);
    }
    
    /**
     * التحقق من كون طريقة الشحن داخلية
     */
    private function isInternalShippingMethod($extension_code) {
        $internal_methods = ['pickup', 'free', 'flat', 'internal_courier'];
        return in_array($extension_code, $internal_methods);
    }
    
    /**
     * التحقق من دعم التتبع
     */
    private function supportsTracking($extension_code) {
        $tracking_methods = ['aramex', 'bosta', 'dhl', 'fedex', 'ups'];
        return in_array($extension_code, $tracking_methods);
    }
    
    /**
     * التحقق من دعم الدفع عند الاستلام
     */
    private function supportsCOD($extension_code) {
        $cod_methods = ['aramex', 'bosta', 'internal_courier', 'cod'];
        return in_array($extension_code, $cod_methods);
    }
    
    /**
     * التحقق من دعم API
     */
    private function supportsAPI($extension_code) {
        $api_methods = ['aramex', 'bosta', 'dhl', 'fedex', 'ups'];
        return in_array($extension_code, $api_methods);
    }
    
    /**
     * تحديث حالة الشحن في OpenCart
     */
    public function updateOpenCartOrderStatus($order_id, $status, $comment = '') {
        // تحديد حالة الطلب في OpenCart بناءً على حالة الشحن
        $status_mapping = [
            'pending' => 1,        // Pending
            'assigned' => 16,      // Assigned
            'picked_up' => 17,     // Shipped
            'in_transit' => 17,    // Shipped
            'delivered' => 5,      // Complete
            'failed' => 10,        // Failed
            'returned' => 11,      // Returned
            'cancelled' => 7       // Canceled
        ];
        
        $order_status_id = isset($status_mapping[$status]) ? $status_mapping[$status] : 1;
        
        // تحديث حالة الطلب
        $this->db->query("
            UPDATE cod_order SET 
            order_status_id = '" . (int)$order_status_id . "',
            date_modified = NOW()
            WHERE order_id = '" . (int)$order_id . "'
        ");
        
        // إضافة سجل في تاريخ الطلب
        $this->db->query("
            INSERT INTO cod_order_history SET 
            order_id = '" . (int)$order_id . "',
            order_status_id = '" . (int)$order_status_id . "',
            notify = 1,
            comment = '" . $this->db->escape($comment) . "',
            date_added = NOW()
        ");
        
        // إرسال إشعار للعميل
        $this->sendOrderStatusNotification($order_id, $status, $comment);
    }
    
    /**
     * إرسال إشعار تحديث حالة الطلب
     */
    private function sendOrderStatusNotification($order_id, $status, $comment) {
        // الحصول على بيانات الطلب
        $order_query = $this->db->query("
            SELECT o.*, CONCAT(o.firstname, ' ', o.lastname) as customer_name
            FROM cod_order o 
            WHERE o.order_id = '" . (int)$order_id . "'
        ");
        
        if ($order_query->num_rows) {
            $order = $order_query->row;
            
            // إنشاء إشعار في النظام المركزي
            $this->db->query("
                INSERT INTO cod_unified_notification SET 
                user_id = '" . (int)$order['customer_id'] . "',
                title = 'تحديث حالة الطلب رقم " . $order_id . "',
                message = '" . $this->db->escape($this->getStatusText($status)) . "',
                type = 'order_status_update',
                priority = 'medium',
                reference_type = 'order',
                reference_id = '" . (int)$order_id . "',
                created_by = '" . (int)$this->user->getId() . "',
                created_at = NOW()
            ");
            
            // إرسال بريد إلكتروني
            $this->load->model('setting/mail');
            
            $subject = 'تحديث حالة طلبكم رقم ' . $order_id;
            $message = "عزيزي/عزيزتي " . $order['customer_name'] . ",\n\n";
            $message .= "تم تحديث حالة طلبكم رقم " . $order_id . " إلى: " . $this->getStatusText($status) . "\n\n";
            
            if ($comment) {
                $message .= "ملاحظات: " . $comment . "\n\n";
            }
            
            $message .= "شكراً لتسوقكم معنا.";
            
            $this->model_setting_mail->send($order['email'], $subject, $message);
        }
    }
    
    /**
     * الحصول على نص الحالة
     */
    private function getStatusText($status) {
        $status_texts = [
            'pending' => 'في الانتظار',
            'assigned' => 'تم التعيين للمندوب',
            'picked_up' => 'تم الاستلام',
            'in_transit' => 'في الطريق',
            'delivered' => 'تم التسليم',
            'failed' => 'فشل التسليم',
            'returned' => 'تم الإرجاع',
            'cancelled' => 'ملغي'
        ];
        
        return isset($status_texts[$status]) ? $status_texts[$status] : $status;
    }
    
    /**
     * الحصول على إعدادات طريقة الشحن
     */
    public function getShippingMethodSettings($extension_code) {
        $settings = [];
        
        // الحصول على جميع الإعدادات المرتبطة بطريقة الشحن
        $query = $this->db->query("
            SELECT `key`, `value` FROM cod_setting 
            WHERE `key` LIKE 'shipping_" . $this->db->escape($extension_code) . "_%'
        ");
        
        foreach ($query->rows as $setting) {
            $key = str_replace('shipping_' . $extension_code . '_', '', $setting['key']);
            $settings[$key] = $setting['value'];
        }
        
        return $settings;
    }
}
