<?php
/**
 * AYM ERP System: Advanced Order Processing Model
 * 
 * نموذج تنفيذ الطلبات المتقدم - مطور للشركات الحقيقية
 * 
 * الميزات المتقدمة:
 * - إدارة سلة طلبات متقدمة مع الوحدات والخيارات
 * - حساب الضرائب التلقائي مع SDK المصري
 * - تكامل مع نظام المخزون المتقدم
 * - دعم التسعير المتدرج والخصومات
 * - تحقق من المخزون في الوقت الفعلي
 * - تكامل مع نظام WAC
 * - دعم التقسيط والدفع المتعدد
 * 
 * @package    AYM ERP
 * @author     AYM Development Team
 * @copyright  2024 AYM ERP Systems
 * @license    Commercial License
 * @version    1.0.0
 * @since      2024-01-15
 */

class ModelSaleOrderProcessing extends Model {
    
    private $order_cart_key = 'order_processing_cart';
    
    /**
     * إضافة منتج لسلة الطلب
     * 
     * مع دعم:
     * - الوحدات المتعددة والتحويل
     * - الخيارات المتقدمة
     * - التسعير المتدرج
     * - حساب الضرائب التلقائي
     */
    public function addToOrderCart($product_id, $quantity, $options = array(), $unit_id = 0, $pricing_type = 'retail', $customer_id = 0, $branch_id = 0) {
        // التحقق من صحة البيانات
        if (!$product_id || $quantity <= 0) {
            return false;
        }
        
        // جلب معلومات المنتج
        $product_info = $this->getProductInfo($product_id);
        if (!$product_info) {
            return false;
        }
        
        // التحقق من الوحدة
        if (!$unit_id) {
            $unit_id = $this->getProductBaseUnit($product_id);
        }
        
        // التحقق من المخزون
        $stock_check = $this->checkStock($product_id, $unit_id, $quantity, $branch_id);
        if (!$stock_check['available']) {
            return false;
        }
        
        // حساب السعر حسب نوع التسعير
        $price = $this->calculatePrice($product_id, $unit_id, $pricing_type, $customer_id, $quantity, $options);
        
        // حساب الضرائب
        $tax_info = $this->calculateTax($product_id, $price, $customer_id);
        
        // إنشاء مفتاح فريد للمنتج في السلة
        $cart_key = $this->generateCartKey($product_id, $unit_id, $options);
        
        // إعداد بيانات المنتج
        $cart_item = array(
            'cart_key' => $cart_key,
            'product_id' => $product_id,
            'name' => $product_info['name'],
            'model' => $product_info['model'],
            'unit_id' => $unit_id,
            'unit_name' => $this->getUnitName($unit_id),
            'quantity' => $quantity,
            'price' => $price,
            'total' => $price * $quantity,
            'tax_rate' => $tax_info['rate'],
            'tax_amount' => $tax_info['amount'] * $quantity,
            'options' => $options,
            'option_text' => $this->formatOptions($product_id, $options),
            'pricing_type' => $pricing_type,
            'date_added' => date('Y-m-d H:i:s')
        );
        
        // إضافة للسلة
        if (!isset($this->session->data[$this->order_cart_key])) {
            $this->session->data[$this->order_cart_key] = array();
        }
        
        // إذا كان المنتج موجود، تحديث الكمية
        if (isset($this->session->data[$this->order_cart_key][$cart_key])) {
            $this->session->data[$this->order_cart_key][$cart_key]['quantity'] += $quantity;
            $this->session->data[$this->order_cart_key][$cart_key]['total'] = 
                $this->session->data[$this->order_cart_key][$cart_key]['price'] * 
                $this->session->data[$this->order_cart_key][$cart_key]['quantity'];
            $this->session->data[$this->order_cart_key][$cart_key]['tax_amount'] = 
                $tax_info['amount'] * $this->session->data[$this->order_cart_key][$cart_key]['quantity'];
        } else {
            $this->session->data[$this->order_cart_key][$cart_key] = $cart_item;
        }
        
        return true;
    }
    
    /**
     * تحديث كمية منتج في السلة
     */
    public function updateOrderCartQuantity($cart_key, $quantity) {
        if (!isset($this->session->data[$this->order_cart_key][$cart_key])) {
            return false;
        }
        
        if ($quantity <= 0) {
            return $this->removeFromOrderCart($cart_key);
        }
        
        $cart_item = &$this->session->data[$this->order_cart_key][$cart_key];
        
        // التحقق من المخزون للكمية الجديدة
        $stock_check = $this->checkStock($cart_item['product_id'], $cart_item['unit_id'], $quantity);
        if (!$stock_check['available']) {
            return false;
        }
        
        // تحديث الكمية والإجماليات
        $cart_item['quantity'] = $quantity;
        $cart_item['total'] = $cart_item['price'] * $quantity;
        
        // إعادة حساب الضرائب
        $tax_info = $this->calculateTax($cart_item['product_id'], $cart_item['price']);
        $cart_item['tax_amount'] = $tax_info['amount'] * $quantity;
        
        return true;
    }
    
    /**
     * إزالة منتج من السلة
     */
    public function removeFromOrderCart($cart_key) {
        if (isset($this->session->data[$this->order_cart_key][$cart_key])) {
            unset($this->session->data[$this->order_cart_key][$cart_key]);
            return true;
        }
        return false;
    }
    
    /**
     * مسح السلة بالكامل
     */
    public function clearOrderCart() {
        $this->session->data[$this->order_cart_key] = array();
        return true;
    }
    
    /**
     * الحصول على محتويات السلة مع الإجماليات
     */
    public function getOrderCart() {
        $products = isset($this->session->data[$this->order_cart_key]) ? 
            $this->session->data[$this->order_cart_key] : array();
        
        $subtotal = 0;
        $tax_total = 0;
        $total = 0;
        
        foreach ($products as $product) {
            $subtotal += $product['total'];
            $tax_total += $product['tax_amount'];
        }
        
        $total = $subtotal + $tax_total;
        
        // إعداد تفاصيل الإجماليات
        $totals = array(
            array(
                'title' => $this->language->get('text_subtotal'),
                'text' => $this->currency->format($subtotal, $this->config->get('config_currency')),
                'value' => $subtotal,
                'sort_order' => 1
            ),
            array(
                'title' => $this->language->get('text_tax'),
                'text' => $this->currency->format($tax_total, $this->config->get('config_currency')),
                'value' => $tax_total,
                'sort_order' => 2
            ),
            array(
                'title' => $this->language->get('text_total'),
                'text' => $this->currency->format($total, $this->config->get('config_currency')),
                'value' => $total,
                'sort_order' => 3
            )
        );
        
        return array(
            'products' => array_values($products),
            'totals' => $totals,
            'subtotal' => $subtotal,
            'tax_total' => $tax_total,
            'total' => $total
        );
    }
    
    /**
     * التحقق من توفر المخزون
     */
    public function checkStock($product_id, $unit_id, $quantity, $branch_id = 0) {
        $this->load->model('inventory/inventory');
        
        // جلب المخزون المتاح
        $stock_info = $this->model_inventory_inventory->getProductStock($product_id, $unit_id, $branch_id);
        
        $available_quantity = $stock_info ? $stock_info['quantity'] : 0;
        $available = $available_quantity >= $quantity;
        
        return array(
            'available' => $available,
            'available_quantity' => $available_quantity,
            'requested_quantity' => $quantity
        );
    }
    
    /**
     * حساب السعر حسب نوع التسعير
     */
    public function calculatePrice($product_id, $unit_id, $pricing_type, $customer_id = 0, $quantity = 1, $options = array()) {
        $this->load->model('pos/pos');
        
        // استخدام نموذج POS لحساب السعر المتقدم
        $price_info = $this->model_pos_pos->getProductPrice($product_id, $unit_id, $pricing_type, $customer_id, $quantity);
        
        $base_price = $price_info['price'];
        
        // إضافة أسعار الخيارات
        if (!empty($options)) {
            $option_price = $this->calculateOptionsPrice($product_id, $options);
            $base_price += $option_price;
        }
        
        return $base_price;
    }
    
    /**
     * حساب الضرائب
     */
    public function calculateTax($product_id, $price, $customer_id = 0) {
        $this->load->model('localisation/tax_class');
        $this->load->model('customer/customer');
        
        // جلب معلومات المنتج والعميل
        $product_info = $this->getProductInfo($product_id);
        $customer_info = $customer_id ? $this->model_customer_customer->getCustomer($customer_id) : null;
        
        $tax_class_id = $product_info['tax_class_id'];
        $tax_rate = 0;
        $tax_amount = 0;
        
        if ($tax_class_id) {
            // حساب الضريبة حسب العميل والمنطقة
            $tax_rates = $this->model_localisation_tax_class->getTaxRates($tax_class_id);
            
            foreach ($tax_rates as $tax_rate_info) {
                $tax_rate += $tax_rate_info['rate'];
            }
            
            $tax_amount = ($price * $tax_rate) / 100;
        }
        
        return array(
            'rate' => $tax_rate,
            'amount' => $tax_amount
        );
    }
    
    /**
     * إعداد بيانات الطلب للإنشاء
     */
    public function prepareOrderData($customer_id, $payment_method, $shipping_method, $notes, $installment_plan, $order_cart) {
        $this->load->model('customer/customer');
        $this->load->model('localisation/currency');
        
        // جلب معلومات العميل
        $customer_info = $this->model_customer_customer->getCustomer($customer_id);
        
        if (!$customer_info) {
            return false;
        }
        
        // إعداد بيانات الطلب الأساسية
        $order_data = array(
            'invoice_prefix' => $this->config->get('config_invoice_prefix'),
            'store_id' => $this->config->get('config_store_id'),
            'store_name' => $this->config->get('config_name'),
            'store_url' => $this->config->get('config_url'),
            
            // معلومات العميل
            'customer_id' => $customer_id,
            'customer_group_id' => $customer_info['customer_group_id'],
            'firstname' => $customer_info['firstname'],
            'lastname' => $customer_info['lastname'],
            'email' => $customer_info['email'],
            'telephone' => $customer_info['telephone'],
            
            // عناوين الفوترة والشحن
            'payment_firstname' => $customer_info['firstname'],
            'payment_lastname' => $customer_info['lastname'],
            'payment_company' => $customer_info['company'],
            'payment_address_1' => $customer_info['address_1'],
            'payment_address_2' => $customer_info['address_2'],
            'payment_city' => $customer_info['city'],
            'payment_postcode' => $customer_info['postcode'],
            'payment_country' => $customer_info['country'],
            'payment_country_id' => $customer_info['country_id'],
            'payment_zone' => $customer_info['zone'],
            'payment_zone_id' => $customer_info['zone_id'],
            'payment_method' => $payment_method,
            'payment_code' => $payment_method,
            
            'shipping_firstname' => $customer_info['firstname'],
            'shipping_lastname' => $customer_info['lastname'],
            'shipping_company' => $customer_info['company'],
            'shipping_address_1' => $customer_info['address_1'],
            'shipping_address_2' => $customer_info['address_2'],
            'shipping_city' => $customer_info['city'],
            'shipping_postcode' => $customer_info['postcode'],
            'shipping_country' => $customer_info['country'],
            'shipping_country_id' => $customer_info['country_id'],
            'shipping_zone' => $customer_info['zone'],
            'shipping_zone_id' => $customer_info['zone_id'],
            'shipping_method' => $shipping_method,
            'shipping_code' => $shipping_method,
            
            // تفاصيل الطلب
            'comment' => $notes,
            'total' => $order_cart['total'],
            'order_status_id' => $this->config->get('config_order_status_id'),
            'affiliate_id' => 0,
            'commission' => 0,
            'language_id' => $this->config->get('config_language_id'),
            'currency_id' => $this->config->get('config_currency_id'),
            'currency_code' => $this->config->get('config_currency'),
            'currency_value' => 1.0,
            'ip' => $this->request->server['REMOTE_ADDR'],
            'forwarded_ip' => isset($this->request->server['HTTP_X_FORWARDED_FOR']) ? $this->request->server['HTTP_X_FORWARDED_FOR'] : '',
            'user_agent' => $this->request->server['HTTP_USER_AGENT'],
            'accept_language' => $this->request->server['HTTP_ACCEPT_LANGUAGE'],
            
            // معلومات POS
            'order_posuser_id' => $this->user->getId(),
            'order_posuser_name' => $this->user->getUserName(),
            'shift_id' => isset($this->session->data['active_shift']) ? $this->session->data['active_shift']['shift_id'] : 0,
            
            // منتجات الطلب
            'order_product' => array(),
            'order_total' => $order_cart['totals']
        );
        
        // إضافة منتجات الطلب
        foreach ($order_cart['products'] as $product) {
            $order_product = array(
                'product_id' => $product['product_id'],
                'name' => $product['name'],
                'model' => $product['model'],
                'unit_id' => $product['unit_id'],
                'quantity' => $product['quantity'],
                'price' => $product['price'],
                'total' => $product['total'],
                'tax' => $product['tax_amount'],
                'reward' => 0
            );
            
            // إضافة الخيارات
            if (!empty($product['options'])) {
                $order_product['option'] = array();
                foreach ($product['options'] as $option_id => $option_value_id) {
                    $option_info = $this->getOptionInfo($option_id, $option_value_id);
                    if ($option_info) {
                        $order_product['option'][] = array(
                            'product_option_id' => $option_info['product_option_id'],
                            'product_option_value_id' => $option_value_id,
                            'option_id' => $option_id,
                            'option_value_id' => $option_value_id,
                            'name' => $option_info['name'],
                            'value' => $option_info['value'],
                            'type' => $option_info['type']
                        );
                    }
                }
            }
            
            $order_data['order_product'][] = $order_product;
        }
        
        // إضافة معلومات التقسيط إذا وجدت
        if ($installment_plan) {
            $order_data['installment_plan'] = $installment_plan;
        }
        
        return $order_data;
    }
    
    /**
     * وظائف مساعدة
     */
    
    private function getProductInfo($product_id) {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product p 
            LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id) 
            WHERE p.product_id = '" . (int)$product_id . "' 
            AND pd.language_id = '" . (int)$this->config->get('config_language_id') . "'");
        
        return $query->row;
    }
    
    private function getProductBaseUnit($product_id) {
        $query = $this->db->query("SELECT unit_id FROM " . DB_PREFIX . "product_unit 
            WHERE product_id = '" . (int)$product_id . "' 
            AND unit_type = 'base' 
            LIMIT 1");
        
        return $query->row ? $query->row['unit_id'] : 37; // الوحدة الافتراضية
    }
    
    private function getUnitName($unit_id) {
        $query = $this->db->query("SELECT desc_ar FROM " . DB_PREFIX . "unit 
            WHERE unit_id = '" . (int)$unit_id . "'");
        
        return $query->row ? $query->row['desc_ar'] : '';
    }
    
    private function generateCartKey($product_id, $unit_id, $options) {
        $option_string = '';
        if (!empty($options)) {
            ksort($options);
            $option_string = serialize($options);
        }
        
        return md5($product_id . ':' . $unit_id . ':' . $option_string);
    }
    
    private function formatOptions($product_id, $options) {
        if (empty($options)) {
            return '';
        }
        
        $option_texts = array();
        foreach ($options as $option_id => $option_value_id) {
            $option_info = $this->getOptionInfo($option_id, $option_value_id);
            if ($option_info) {
                $option_texts[] = $option_info['name'] . ': ' . $option_info['value'];
            }
        }
        
        return implode(', ', $option_texts);
    }
    
    private function calculateOptionsPrice($product_id, $options) {
        $total_price = 0;
        
        foreach ($options as $option_id => $option_value_id) {
            $query = $this->db->query("SELECT price, price_prefix FROM " . DB_PREFIX . "product_option_value 
                WHERE product_id = '" . (int)$product_id . "' 
                AND option_id = '" . (int)$option_id . "' 
                AND option_value_id = '" . (int)$option_value_id . "'");
            
            if ($query->row) {
                if ($query->row['price_prefix'] == '+') {
                    $total_price += $query->row['price'];
                } elseif ($query->row['price_prefix'] == '-') {
                    $total_price -= $query->row['price'];
                }
            }
        }
        
        return $total_price;
    }
    
    private function getOptionInfo($option_id, $option_value_id) {
        $query = $this->db->query("SELECT po.product_option_id, od.name, ovd.name as value, o.type 
            FROM " . DB_PREFIX . "product_option po 
            LEFT JOIN " . DB_PREFIX . "option_description od ON (po.option_id = od.option_id) 
            LEFT JOIN " . DB_PREFIX . "option_value_description ovd ON (ovd.option_value_id = '" . (int)$option_value_id . "') 
            LEFT JOIN " . DB_PREFIX . "option o ON (po.option_id = o.option_id) 
            WHERE po.option_id = '" . (int)$option_id . "' 
            AND od.language_id = '" . (int)$this->config->get('config_language_id') . "' 
            AND ovd.language_id = '" . (int)$this->config->get('config_language_id') . "'");
        
        return $query->row;
    }
}
