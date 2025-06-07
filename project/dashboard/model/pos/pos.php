<?php
class ModelPosPos extends Model {
    public function __construct($registry) {
        parent::__construct($registry);
        $this->load->model('catalog/product');
    }  
    
    public function getCurrentUserBranch() {
        $user_id = $this->user->getId();
        $query = $this->db->query("SELECT b.* FROM " . DB_PREFIX . "user u 
                                   LEFT JOIN " . DB_PREFIX . "branch b ON u.branch_id = b.branch_id 
                                   WHERE u.user_id = '" . (int)$user_id . "'");
        return $query->row;
    }

public function getProductByBarcode($barcode) {
    $query = $this->db->query("SELECT pb.product_id, pb.unit_id, pb.product_option_id, pb.product_option_value_id, 
                               p.model, p.price, p.tax_class_id, pd.name
                               FROM " . DB_PREFIX . "product_barcode pb
                               LEFT JOIN " . DB_PREFIX . "product p ON (pb.product_id = p.product_id)
                               LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id)
                               WHERE pb.barcode = '" . $this->db->escape($barcode) . "'
                               AND pd.language_id = '" . (int)$this->config->get('config_language_id') . "'");

    if ($query->num_rows) {
        return $query->row;
    }

    return false;
}


public function convertProductUnits($product_id, $from_unit_id, $to_unit_id, $quantity, $branch_id) {
    $units = $this->getProductUnits($product_id);
    $base_unit_id = null;
    foreach ($units as $unit) {
        if ($unit['unit_type'] == 'base') {
            $base_unit_id = $unit['unit_id'];
            break;
        }
    }

    if ($base_unit_id === null || $to_unit_id != $base_unit_id) {
        // لا نسمح بالتحويل إلا إلى الوحدة الأساسية
        return false;
    }

    $conversion_factor = $this->getConversionFactor($product_id, $from_unit_id, $to_unit_id);
    $from_quantity = $quantity / $conversion_factor;
    
    $this->db->query("UPDATE " . DB_PREFIX . "product_inventory 
                      SET quantity = quantity - " . (float)$from_quantity . " 
                      WHERE product_id = '" . (int)$product_id . "' 
                      AND unit_id = '" . (int)$from_unit_id . "' 
                      AND branch_id = '" . (int)$branch_id . "'");
    
    $this->db->query("UPDATE " . DB_PREFIX . "product_inventory 
                      SET quantity = quantity + " . (float)$quantity . " 
                      WHERE product_id = '" . (int)$product_id . "' 
                      AND unit_id = '" . (int)$to_unit_id . "' 
                      AND branch_id = '" . (int)$branch_id . "'");
    
    $this->addUnitConversionHistory($product_id, $from_unit_id, $to_unit_id, $from_quantity, $quantity, $branch_id);

    return true;
}

public function checkAndPrepareInventory($product_id, $requested_quantity, $requested_unit_id, $branch_id) {
    $inventory = $this->getProductInventory($product_id, $branch_id);
    $units = $this->getProductUnits($product_id);

    $base_unit = null;
    foreach ($units as $unit) {
        if ($unit['unit_type'] == 'base') {
            $base_unit = $unit;
            break;
        }
    }

    if (!$base_unit) {
        return array('success' => false, 'message' => $this->language->get('error_no_base_unit'));
    }

    $available_quantity = 0;
    $conversion_needed = false;
    $from_unit = null;

    foreach ($inventory as $item) {
        if ($item['unit_id'] == $requested_unit_id) {
            $available_quantity = $item['quantity'];
            break;
        }
    }

    if ($available_quantity < $requested_quantity) {
        // فقط نبحث عن الوحدات الإضافية للتحويل إلى الوحدة الأساسية
        if ($requested_unit_id == $base_unit['unit_id']) {
            foreach ($inventory as $item) {
                if ($item['quantity'] > 0 && $item['unit_id'] != $base_unit['unit_id']) {
                    $converted_quantity = $this->convertUnits($item['unit_id'], $requested_unit_id, $item['quantity']);
                    if ($converted_quantity + $available_quantity >= $requested_quantity) {
                        $conversion_needed = true;
                        $from_unit = $item;
                        $available_quantity += $converted_quantity;
                        break;
                    }
                }
            }
        }
    }

    if ($available_quantity >= $requested_quantity) {
        $result = array(
            'success' => true,
            'quantity' => $requested_quantity,
            'unit_id' => $requested_unit_id,
            'converted' => $conversion_needed
        );

        if ($conversion_needed) {
            $result['from_unit_name'] = $from_unit['unit_name'];
            $result['to_unit_name'] = $this->getUnitName($requested_unit_id);
            
            // نقوم بتخزين معلومات التحويل في الجلسة
            if (!isset($this->session->data['unit_conversions'])) {
                $this->session->data['unit_conversions'] = array();
            }
            $this->session->data['unit_conversions'][] = array(
                'product_id' => $product_id,
                'from_unit_id' => $from_unit['unit_id'],
                'to_unit_id' => $requested_unit_id,
                'quantity' => $requested_quantity,
                'branch_id' => $branch_id
            );
        }

        return $result;
    } else {
        return array('success' => false, 'message' => $this->language->get('error_insufficient_stock'));
    }
}

public function convertUnits($from_unit_id, $to_unit_id, $quantity) {
    $query = $this->db->query("SELECT pu1.conversion_factor as from_factor, pu2.conversion_factor as to_factor 
                               FROM " . DB_PREFIX . "product_unit pu1 
                               JOIN " . DB_PREFIX . "product_unit pu2 
                               ON pu1.product_id = pu2.product_id 
                               WHERE pu1.unit_id = '" . (int)$from_unit_id . "' 
                               AND pu2.unit_id = '" . (int)$to_unit_id . "'");
    
    if ($query->num_rows) {
        return ($quantity * $query->row['from_factor']) / $query->row['to_factor'];
    }
    
    return 0;
}

public function getUnitName($unit_id) {
    $query = $this->db->query("SELECT CONCAT(desc_en, ' - ', desc_ar) AS unit_name FROM " . DB_PREFIX . "unit WHERE unit_id = '" . (int)$unit_id . "'");
    return $query->row ? $query->row['unit_name'] : '';
}    
public function searchProducts($query, $category_id, $pricing_type, $branch_id) {
    $sql = "SELECT p.product_id, pd.name, p.image, 
                   pp.base_price, pp.special_price, pp.wholesale_price, pp.half_wholesale_price, pp.custom_price,
                   (SELECT SUM(quantity) FROM " . DB_PREFIX . "product_inventory WHERE product_id = p.product_id AND branch_id = '" . (int)$branch_id . "') as branch_quantity
            FROM " . DB_PREFIX . "product p 
            LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id) 
            LEFT JOIN " . DB_PREFIX . "product_to_category p2c ON (p.product_id = p2c.product_id)
            LEFT JOIN " . DB_PREFIX . "product_pricing pp ON (p.product_id = pp.product_id)
            WHERE pd.language_id = '" . (int)$this->config->get('config_language_id') . "' 
            AND p.status = '1'";

    if (!empty($query)) {
        $sql .= " AND (pd.name LIKE '%" . $this->db->escape($query) . "%' OR p.model LIKE '%" . $this->db->escape($query) . "%')";
    }

    if ($category_id > 0) {
        $sql .= " AND p2c.category_id = '" . (int)$category_id . "'";
    }

    if ($branch_id > 0) {
        $sql .= " AND branch_quantity > 0";
    }

    $sql .= " GROUP BY p.product_id";

    $query = $this->db->query($sql);

    $results = array();
    foreach ($query->rows as $row) {
        $prices = array(
            'special_price'  => $row['special_price'] > 0 ? $row['special_price'] : 0,
            'retail'         => $row['special_price'] > 0 ? $row['special_price'] : $row['base_price'],
            'wholesale'      => $row['wholesale_price'] > 0 ? $row['wholesale_price'] : $row['base_price'],
            'half_wholesale' => $row['half_wholesale_price'] > 0 ? $row['half_wholesale_price'] : $row['base_price'],
            'custom'         => $row['custom_price'] > 0 ? $row['custom_price'] : $row['base_price'],
            'base_price'     => $row['base_price'],
            'special'        => $row['special_price'] > 0 ? $row['special_price'] : false
        );

        $results[] = array(
            'product_id' => $row['product_id'],
            'name'       => $row['name'],
            'image'      => $row['image'],
            'prices'     => $prices,
            'stock'      => $row['branch_quantity'],
            'has_options' => $this->hasOptions($row['product_id']),
            'units'      => $this->getProductUnits($row['product_id'])
        );
    }

    return $results;
}

private function getProductPrices($product_id) {
    $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_pricing WHERE product_id = '" . (int)$product_id . "'");
    $prices = array();
    foreach ($query->rows as $row) {
        $prices[$row['unit_id']] = array(
            'retail' => $row['special_price']?$row['special_price']:$row['base_price'],
            'base_price' => $row['base_price'],
            'special_price' => $row['special_price'],
            'wholesale' => $row['wholesale_price'],
            'half_wholesale' => $row['half_wholesale_price'],
            'custom' => $row['custom_price']
        );
    }
    return $prices;
}

public function getProductPricing($product_id) {
    $query = $this->db->query("SELECT pp.*, u.desc_en, u.desc_ar 
                               FROM " . DB_PREFIX . "product_pricing pp
                               LEFT JOIN " . DB_PREFIX . "unit u ON (pp.unit_id = u.unit_id)
                               WHERE pp.product_id = '" . (int)$product_id . "'");
    
    $pricing = array();
    foreach ($query->rows as $row) {
        $pricing[$row['unit_id']] = array(
            'unit_name' => $row['desc_en'] . ' - ' . $row['desc_ar'],
            'base_price' => $row['base_price'],
            'special_price' => $row['special_price'],
            'wholesale_price' => $row['wholesale_price'],
            'half_wholesale_price' => $row['half_wholesale_price'],
            'custom_price' => $row['custom_price']
        );
    }
    
    return $pricing;
}

    public function getProductOptions($product_id) {
        $product_option_data = array();

        $product_option_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_option po LEFT JOIN `" . DB_PREFIX . "option` o ON (po.option_id = o.option_id) LEFT JOIN " . DB_PREFIX . "option_description od ON (o.option_id = od.option_id) WHERE po.product_id = '" . (int)$product_id . "' AND od.language_id = '" . (int)$this->config->get('config_language_id') . "'");

        foreach ($product_option_query->rows as $product_option) {
            $product_option_value_data = array();

            $product_option_value_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_option_value pov LEFT JOIN " . DB_PREFIX . "option_value ov ON (pov.option_value_id = ov.option_value_id) LEFT JOIN " . DB_PREFIX . "option_value_description ovd ON (ov.option_value_id = ovd.option_value_id) WHERE pov.product_id = '" . (int)$product_id . "' AND pov.product_option_id = '" . (int)$product_option['product_option_id'] . "' AND ovd.language_id = '" . (int)$this->config->get('config_language_id') . "'");

            foreach ($product_option_value_query->rows as $product_option_value) {
                $product_option_value_data[] = array(
                    'product_option_value_id' => $product_option_value['product_option_value_id'],
                    'option_value_id'         => $product_option_value['option_value_id'],
                    'name'                    => $product_option_value['name'],
                    'price'                   => $product_option_value['price'],
                    'price_prefix'            => $product_option_value['price_prefix']
                );
            }

            $product_option_data[] = array(
                'product_option_id' => $product_option['product_option_id'],
                'option_id'         => $product_option['option_id'],
                'unit_id'         => $product_option['unit_id'],
                'name'              => $product_option['name'],
                'type'              => $product_option['type'],
                'product_option_value' => $product_option_value_data,
                'required'          => $product_option['required']
            );
        }

        return $product_option_data;
    }

public function getProductBaseUnit($product_id) {
    $query = $this->db->query("SELECT unit_id FROM " . DB_PREFIX . "product_unit WHERE product_id = '" . (int)$product_id . "' AND unit_type = 'base'");
    
    if ($query->num_rows) {
        return $query->row['unit_id'];
    }
    
    return false;
}

    /**
     * إضافة حركة منتج (مساعدة)
     *
     * @param int    $product_id
     * @param string $type (purchase,sale,adjustment,transfer,import,...)
     * @param float  $quantity
     * @param int    $unit_id
     * @param string $reference
     * @return bool
     */

    public function addInventoryHistory($product_id, $unit_id,$quantity_change,$action_type, $reference = '')
    {
        $this->db->query("INSERT INTO `" . DB_PREFIX . "product_movement` SET
            product_id = '" . (int)$product_id . "',
            type       = '" . $this->db->escape($action_type) . "',
            date_added = NOW(),
            quantity   = '" . (float)$quantity_change . "',
            unit_id    = '" . (int)$unit_id . "',
            reference  = '" . $this->db->escape($reference) . "'
        ");
        return true;
    }
    

public function addUnitConversionHistory($product_id, $from_unit_id, $to_unit_id, $from_quantity, $to_quantity) {
    $this->db->query("INSERT INTO " . DB_PREFIX . "unit_conversion_history SET 
        product_id = '" . (int)$product_id . "', 
        from_unit_id = '" . (int)$from_unit_id . "', 
        to_unit_id = '" . (int)$to_unit_id . "', 
        from_quantity = '" . (float)$from_quantity . "', 
        to_quantity = '" . (float)$to_quantity . "', 
        user_id = '" . (int)$this->user->getId() . "', 
        date_added = NOW()");
}

public function deductInventory($product_id, $quantity, $from_unit_id, $to_unit_id, $branch_id) {
    if ($branch_id == 0) { // إذا كان المدير يقوم بالعملية
        // الحصول على قائمة الفروع التي تحتوي على الكمية المطلوبة
        $query = $this->db->query("SELECT branch_id, quantity 
                                   FROM " . DB_PREFIX . "product_inventory 
                                   WHERE product_id = '" . (int)$product_id . "' 
                                   AND unit_id = '" . (int)$from_unit_id . "' 
                                   AND quantity >= '" . (float)$quantity . "' 
                                   ORDER BY quantity DESC 
                                   LIMIT 1");

        if ($query->num_rows) {
            $branch_id = $query->row['branch_id'];
        } else {
            // إذا لم يكن هناك فرع يحتوي على الكمية المطلوبة، لن يتم إجراء خصم
            return false;
        }
    }

    // التأكد من أن الكمية المحولة تخصم من الوحدة الصحيحة
    $branch_condition = "AND branch_id = '" . (int)$branch_id . "'";
    
    // خصم الكمية من الوحدة الأصلية
    $this->db->query("UPDATE " . DB_PREFIX . "product_inventory 
                      SET quantity = quantity - '" . (float)$quantity . "' 
                      WHERE product_id = '" . (int)$product_id . "' 
                      AND unit_id = '" . (int)$from_unit_id . "' 
                      $branch_condition");

    // إضافة الكمية المحولة إلى الوحدة الجديدة
    $this->db->query("UPDATE " . DB_PREFIX . "product_inventory 
                      SET quantity = quantity + '" . (float)$quantity . "' 
                      WHERE product_id = '" . (int)$product_id . "' 
                      AND unit_id = '" . (int)$to_unit_id . "' 
                      $branch_condition");

    return true;
}


// دالة للحصول على عامل التحويل بين الوحدات
private function getConversionRate($from_unit_id, $to_unit_id) {
    // هذا مثال افتراضي. يجب عليك تحديثه حسب كيفية تخزين علاقات التحويل بين الوحدات في النظام الخاص بك
    $query = $this->db->query("SELECT conversion_rate FROM " . DB_PREFIX . "unit_conversion 
                               WHERE from_unit_id = '" . (int)$from_unit_id . "' 
                               AND to_unit_id = '" . (int)$to_unit_id . "'");

    return $query->num_rows ? (float)$query->row['conversion_rate'] : 1.0;
}


public function getProductInventoryx($product_id) {
    $user_id = $this->user->getId();
    $is_admin = $this->user->getGroupId() == '1'; // أو أي طريقة أخرى للتحقق من صلاحيات الإدارة

    if ($is_admin) {
        $query = $this->db->query("SELECT pi.*, 
                                   CONCAT(u.desc_en, ' - ', u.desc_ar) AS unit_name
                                   FROM " . DB_PREFIX . "product_inventory pi
                                   LEFT JOIN " . DB_PREFIX . "unit u ON (pi.unit_id = u.unit_id)
                                   WHERE pi.product_id = '" . (int)$product_id . "'");
    } else {
        $branch_id = $this->user->getBranchId(); // افترض أن هناك دالة للحصول على معرف الفرع للمستخدم
        $query = $this->db->query("SELECT pi.*, 
                                   CONCAT(u.desc_en, ' - ', u.desc_ar) AS unit_name
                                   FROM " . DB_PREFIX . "product_inventory pi
                                   LEFT JOIN " . DB_PREFIX . "unit u ON (pi.unit_id = u.unit_id)
                                   WHERE pi.product_id = '" . (int)$product_id . "'
                                   AND pi.branch_id = '" . (int)$branch_id . "'");
    }

    return $query->rows;
}

    public function getProductUnits($product_id) {
        $query = $this->db->query("SELECT pu.*, CONCAT(u.desc_en, ' - ', u.desc_ar) AS unit_name 
            FROM " . DB_PREFIX . "product_unit pu 
            LEFT JOIN " . DB_PREFIX . "unit u ON (pu.unit_id = u.unit_id) 
            WHERE pu.product_id = '" . (int)$product_id . "'");

        return $query->rows;
    }    
    /*
    public function hasOptions($product_id) {
        $query = $this->db->query("SELECT COUNT(DISTINCT po.product_option_id) AS total FROM " . DB_PREFIX . "product_option po LEFT JOIN " . DB_PREFIX . "option o ON (po.option_id = o.option_id) WHERE po.product_id = '" . (int)$product_id . "' AND o.type IN ('select', 'radio', 'checkbox', 'image')");
        return $query->row['total'] > 0;
    }
    */
public function hasOptions($product_id) {
    $query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "product_option WHERE product_id = '" . (int)$product_id . "'");
    return $query->row['total'] > 0;
}
    
    


    public function createPayTabsInvoice($order_id) {
        $payment_key = $this->config->get('config_payment_paytabs_server_key');
        $profile_id = $this->config->get('config_payment_paytabs_profile_id');

        $order_info = $this->getOrder($order_id);

        if ($order_info) {
            $cart_id = $order_id;
            $cart_amount = $order_info['total'];
            $cart_description = $order_id;
            $customer_details = array(
                "name" => $order_info['firstname'] . " " . $order_info['lastname'],
                "email" => $order_info['email'],
                "street1" => $order_info['payment_address_1'],
                "city" => $order_info['payment_city'],
                "country" => $order_info['payment_iso_code_2']
            );
            $line_items = array();

            $order_products = $this->getOrderProducts($order_id);
            foreach ($order_products as $product) {
                $line_items[] = array(
                    "sku" => $product['model'],
                    "description" => $product['name'],
                    "url" => "",
                    "unit_cost" => $product['price'],
                    "quantity" => $product['quantity'],
                    "net_total" => $product['price'] * $product['quantity'],
                    "discount_rate" => 0,
                    "discount_amount" => 0,
                    "tax_rate" => 0,
                    "tax_total" => 0,
                    "total" => $product['price'] * $product['quantity']
                );
            }

            $invoice_details = array(
                "lang" => "en",
                "shipping_charges" => 0,
                "extra_charges" => 0,
                "extra_discount" => 0,
                "total" => 0,
                "activation_date" => "",
                "expiry_date" => "2024-09-27T13:33:00+04:00",
                "due_date" => "2024-09-26T12:36:00+04:00",
                "disable_edit" => false,
                "line_items" => $line_items
            );

            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => 'https://secure.paytabs.com/payment/invoice/new',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => json_encode(array(
                    "profile_id" => $profile_id,
                    "tran_type" => "sale",
                    "tran_class" => "ecom",
                    "cart_currency" => $order_info['currency_code'],
                    "cart_amount" => $cart_amount,
                    "cart_id" => $cart_id,
                    "cart_description" => $cart_description,
                    "hide_shipping" => true,
                    "customer_ref" => "CUST_" . $order_info['customer_id'],
                    "customer_details" => $customer_details,
                    "invoice" => $invoice_details,
                    "callback" => "",
                    "return" => ""
                )),
                CURLOPT_HTTPHEADER => array(
                    "Authorization: $payment_key",
                    'Content-Type: application/json'
                ),
            ));

            $response = curl_exec($curl);
            curl_close($curl);

            $invoice_response = json_decode($response, true);

            if (isset($invoice_response['tran_ref'])) {
                $this->savepaymentlink($order_id, $invoice_response['tran_ref']);
                $this->sendPaymentLinkSMS($order_info['telephone'], $invoice_response['tran_ref']);
                return $invoice_response;
            }
        }

        return false;
    }

    public function savepaymentlink($order_id, $paymentlink) {
        $this->db->query("INSERT INTO " . DB_PREFIX . "paymentlinks SET order_id = '" . $this->db->escape($order_id) . "', paymentlink = '" . $this->db->escape($paymentlink) . "'");
        return $this->db->getLastId();
    }


    public function getSuspendedCarts() {
        $query = $this->db->query("SELECT s.*, c.firstname, c.lastname 
                                   FROM " . DB_PREFIX . "pos_suspended_sale s 
                                   LEFT JOIN " . DB_PREFIX . "customer c ON s.customer_id = c.customer_id");
        return $query->rows;
    }

    public function restoreCart($suspend_id) {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "pos_suspended_sale WHERE id = '" . (int)$suspend_id . "'");
        $suspended_cart = $query->row;

        // يجب استعادة تفاصيل السلة وتحديث سلة المستخدم الحالية
        $this->cart->clear();
        $cart_data = json_decode($suspended_cart['cart'], true);
        foreach ($cart_data as $product) {
            $this->cart->add($product['product_id'], $product['quantity'], $product['option'], $product['price']);
        }

        // حذف السلة المعلقة بعد استعادتها
        $this->db->query("DELETE FROM " . DB_PREFIX . "pos_suspended_sale WHERE id = '" . (int)$suspend_id . "'");
    }
	
    public function getOrder($order_id) {
        $order_query = $this->db->query("SELECT *, (SELECT CONCAT(c.firstname, ' ', c.lastname) FROM " . DB_PREFIX . "customer c WHERE c.customer_id = o.customer_id) AS customer, (SELECT os.name FROM " . DB_PREFIX . "order_status os WHERE os.order_status_id = o.order_status_id AND os.language_id = '" . (int)$this->config->get('config_language_id') . "') AS order_status FROM `" . DB_PREFIX . "order` o WHERE o.order_id = '" . (int)$order_id . "'");

        if ($order_query->num_rows) {
            $country_query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "country` WHERE country_id = '" . (int)$order_query->row['payment_country_id'] . "'");

            if ($country_query->num_rows) {
                $payment_iso_code_2 = $country_query->row['iso_code_2'];
                $payment_iso_code_3 = $country_query->row['iso_code_3'];
            } else {
                $payment_iso_code_2 = '';
                $payment_iso_code_3 = '';
            }

            $zone_query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "zone` WHERE zone_id = '" . (int)$order_query->row['payment_zone_id'] . "'");

            if ($zone_query->num_rows) {
                $payment_zone_code = $zone_query->row['code'];
            } else {
                $payment_zone_code = '';
            }

            $country_query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "country` WHERE country_id = '" . (int)$order_query->row['shipping_country_id'] . "'");

            if ($country_query->num_rows) {
                $shipping_iso_code_2 = $country_query->row['iso_code_2'];
                $shipping_iso_code_3 = $country_query->row['iso_code_3'];
            } else {
                $shipping_iso_code_2 = '';
                $shipping_iso_code_3 = '';
            }

            $zone_query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "zone` WHERE zone_id = '" . (int)$order_query->row['shipping_zone_id'] . "'");

            if ($zone_query->num_rows) {
                $shipping_zone_code = $zone_query->row['code'];
            } else {
                $shipping_zone_code = '';
            }

            $reward = 0;

            $order_product_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "order_product WHERE order_id = '" . (int)$order_id . "'");

            foreach ($order_product_query->rows as $product) {
                $reward += $product['reward'];
            }

            $this->load->model('customer/customer');

            $affiliate_info = $this->model_customer_customer->getCustomer($order_query->row['affiliate_id']);

            if ($affiliate_info) {
                $affiliate_firstname = $affiliate_info['firstname'];
                $affiliate_lastname = $affiliate_info['lastname'];
            } else {
                $affiliate_firstname = '';
                $affiliate_lastname = '';
            }

            $this->load->model('localisation/language');

            $language_info = $this->model_localisation_language->getLanguage($order_query->row['language_id']);

            if ($language_info) {
                $language_code = $language_info['code'];
            } else {
                $language_code = $this->config->get('config_language');
            }

            return array(
                'order_id'                => $order_query->row['order_id'],
                'invoice_no'              => $order_query->row['invoice_no'],
                'invoice_prefix'          => $order_query->row['invoice_prefix'],
                'store_id'                => $order_query->row['store_id'],
                'store_name'              => $order_query->row['store_name'],
                'store_url'               => $order_query->row['store_url'],
                'customer_id'             => $order_query->row['customer_id'],
                'customer'                => $order_query->row['customer'],
                'customer_group_id'       => $order_query->row['customer_group_id'],
                'firstname'               => $order_query->row['firstname'],
                'lastname'                => $order_query->row['lastname'],
                'email'                   => $order_query->row['email'],
                'telephone'               => $order_query->row['telephone'],
                'custom_field'            => json_decode($order_query->row['custom_field'], true),
                'payment_firstname'       => $order_query->row['payment_firstname'],
                'payment_lastname'        => $order_query->row['payment_lastname'],
                'payment_company'         => $order_query->row['payment_company'],
                'payment_address_1'       => $order_query->row['payment_address_1'],
                'payment_address_2'       => $order_query->row['payment_address_2'],
                'payment_postcode'        => $order_query->row['payment_postcode'],
                'payment_city'            => $order_query->row['payment_city'],
                'payment_zone_id'         => $order_query->row['payment_zone_id'],
                'payment_zone'            => $order_query->row['payment_zone'],
                'payment_zone_code'       => $payment_zone_code,
                'payment_country_id'      => $order_query->row['payment_country_id'],
                'payment_country'         => $order_query->row['payment_country'],
                'payment_iso_code_2'      => $payment_iso_code_2,
                'payment_iso_code_3'      => $payment_iso_code_3,
                'payment_address_format'  => $order_query->row['payment_address_format'],
                'payment_custom_field'    => json_decode($order_query->row['payment_custom_field'], true),
                'payment_method'          => $order_query->row['payment_method'],
                'payment_code'            => $order_query->row['payment_code'],
                'shipping_firstname'      => $order_query->row['shipping_firstname'],
                'shipping_lastname'       => $order_query->row['shipping_lastname'],
                'shipping_company'        => $order_query->row['shipping_company'],
                'shipping_address_1'      => $order_query->row['shipping_address_1'],
                'shipping_address_2'      => $order_query->row['shipping_address_2'],
                'shipping_postcode'       => $order_query->row['shipping_postcode'],
                'shipping_city'           => $order_query->row['shipping_city'],
                'shipping_zone_id'        => $order_query->row['shipping_zone_id'],
                'shipping_zone'           => $order_query->row['shipping_zone'],
                'shipping_zone_code'      => $shipping_zone_code,
                'shipping_country_id'     => $order_query->row['shipping_country_id'],
                'shipping_country'        => $order_query->row['shipping_country'],
                'shipping_iso_code_2'     => $shipping_iso_code_2,
                'shipping_iso_code_3'     => $shipping_iso_code_3,
                'shipping_address_format' => $order_query->row['shipping_address_format'],
                'shipping_custom_field'   => json_decode($order_query->row['shipping_custom_field'], true),
                'shipping_method'         => $order_query->row['shipping_method'],
                'shipping_code'           => $order_query->row['shipping_code'],
                'comment'                 => $order_query->row['comment'],
                'total'                   => $order_query->row['total'],
                'reward'                  => $reward,
                'order_status_id'         => $order_query->row['order_status_id'],
                'order_status'            => $order_query->row['order_status'],
                'affiliate_id'            => $order_query->row['affiliate_id'],
                'affiliate_firstname'     => $affiliate_firstname,
                'affiliate_lastname'      => $affiliate_lastname,
                'commission'              => $order_query->row['commission'],
                'language_id'             => $order_query->row['language_id'],
                'language_code'           => $language_code,
                'currency_id'             => $order_query->row['currency_id'],
                'currency_code'           => $order_query->row['currency_code'],
                'currency_value'          => $order_query->row['currency_value'],
                'ip'                      => $order_query->row['ip'],
                'forwarded_ip'            => $order_query->row['forwarded_ip'],
                'user_agent'              => $order_query->row['user_agent'],
                'accept_language'         => $order_query->row['accept_language'],
                'date_added'              => $order_query->row['date_added'],
                'date_modified'           => $order_query->row['date_modified']
            );
        } else {
            return;
        }
    }

    public function getOrderProducts($order_id) {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "order_product WHERE order_id = '" . (int)$order_id . "'");
        return $query->rows;
    }

    public function sendPaymentLinkSMS($phone, $tran_ref) {
        $payment_key = $this->config->get('config_payment_paytabs_server_key');
        $profile_id = $this->config->get('config_payment_paytabs_profile_id');
        
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://secure.paytabs.com/payment/invoice/$tran_ref/sms",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode(array(
                "profile_id" => $profile_id,
                "customer_details" => array(
                    "phone" => $phone
                )
            )),
            CURLOPT_HTTPHEADER => array(
                "Authorization: $payment_key",
                'Content-Type: application/json'
            ),
        ));

        $response = curl_exec($curl);
        curl_close($curl);

        return $response;
    }

	public function getCoupon($code) {
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "coupon WHERE code = '" . $this->db->escape($code) . "' AND status = '1' AND ((date_start = '0000-00-00' OR date_start < NOW()) AND (date_end = '0000-00-00' OR date_end > NOW()))");
		
		return $query->num_rows ? $query->row : false;
	}

    private function calculateCouponDiscount($coupon_info, $products, $total) {
        $discount = 0;

        if ($coupon_info['type'] == 'P') {
            foreach ($products as $product) {
                $discount += ($product['total'] * $coupon_info['discount'] / 100);
            }
        }

        if ($coupon_info['type'] == 'F') {
            $discount = min($coupon_info['discount'], $total);
        }

        return $discount;
    }

public function getProductInventory($product_id) {
    $query = $this->db->query("SELECT pi.*, 
        b.name AS branch_name, 
        b.type AS branch_type,
        CONCAT(u.desc_en, ' - ', u.desc_ar) AS unit_name 
        FROM " . DB_PREFIX . "product_inventory pi 
        LEFT JOIN " . DB_PREFIX . "branch b ON (pi.branch_id = b.branch_id) 
        LEFT JOIN " . DB_PREFIX . "unit u ON (pi.unit_id = u.unit_id) 
        WHERE pi.product_id = '" . (int)$product_id . "'");
//error_log('getProductInventory Result: ' . print_r($query->rows, true));

    return $query->rows;
}



    public function getTotals($shipping_method = '', $payment_method = '') {
        $this->load->model('pos/cart');
        $this->load->language('pos/pos');

        $total_data = array();
        $total = 0;
        $taxes = array();
        $products = $this->model_pos_cart->getProducts();

        // Calculate product totals and taxes
        foreach ($products as $product) {
            $product_total = $product['price'] * $product['quantity'];
            $total += $product_total;

            if ($product['tax_class_id']) {
                $tax_rates = $this->tax->getRates($product['price'], $product['tax_class_id']);

                foreach ($tax_rates as $tax_rate) {
                    if (!isset($taxes[$tax_rate['tax_rate_id']])) {
                        $taxes[$tax_rate['tax_rate_id']] = ($tax_rate['amount'] * $product['quantity']);
                    } else {
                        $taxes[$tax_rate['tax_rate_id']] += ($tax_rate['amount'] * $product['quantity']);
                    }
                }
            }
        }

        $total_data[] = array(
            'code'       => 'sub_total',
            'title'      => $this->language->get('text_sub_total'),
            'value'      => $total,
            'sort_order' => 1
        );

        // Apply coupon discount if a coupon is stored in the session
        $coupon_discount = 0;
        if (isset($this->session->data['coupon'])) {
            $coupon_info = $this->getCoupon($this->session->data['coupon']);
            if ($coupon_info) {
                $coupon_discount = $this->calculateCouponDiscount($coupon_info, $products, $total);
                $total -= $coupon_discount;

                $total_data[] = array(
                    'code'       => 'coupon',
                    'title'      => sprintf($this->language->get('text_coupon'), $this->session->data['coupon']),
                    'value'      => -$coupon_discount,
                    'sort_order' => 2
                );
            }
        }

        // Calculate shipping cost if a shipping method is provided
        if ($shipping_method) {
            $total_weight = array_sum(array_column($products, 'weight'));
            $shipping_cost = $this->calculateImprovedShippingCost($shipping_method, $total, $total_weight);
            $total += $shipping_cost;
            $total_data[] = array(
                'code'       => 'shipping',
                'title'      => $this->language->get('text_shipping'),
                'value'      => $shipping_cost,
                'sort_order' => 3
            );
        }

        // Calculate total tax
        $tax_total = 0;
        foreach ($taxes as $tax) {
            $tax_total += $tax;
        }

        $total_data[] = array(
            'code'       => 'tax',
            'title'      => $this->language->get('text_tax'),
            'value'      => $tax_total,
            'sort_order' => 4
        );

        // Calculate grand total
        $grand_total = $total + $tax_total;

        $total_data[] = array(
            'code'       => 'total',
            'title'      => $this->language->get('text_total'),
            'value'      => $grand_total,
            'sort_order' => 5
        );

        // Format currency for display
        foreach ($total_data as &$total_item) {
            $total_item['text'] = $this->currency->format($total_item['value'], $this->config->get('config_currency'));
        }

        return array(
            'totals' => $total_data,
            'total'  => $grand_total
        );
    }


	public function addOrder($data) {
		$this->db->query("INSERT INTO `" . DB_PREFIX . "order` SET order_posuser_name = '" . $this->db->escape($data['order_posuser_name']) . "', shift_id = '" . (int)$data['shift_id'] . "', order_posuser_id = '" . (int)$data['order_posuser_id'] . "', invoice_prefix = '" . $this->db->escape($data['invoice_prefix']) . "', store_id = '" . (int)$data['store_id'] . "', store_name = '" . $this->db->escape($data['store_name']) . "', store_url = '" . $this->db->escape($data['store_url']) . "', customer_id = '" . (int)$data['customer_id'] . "', customer_group_id = '" . (int)$data['customer_group_id'] . "', firstname = '" . $this->db->escape($data['firstname']) . "', lastname = '" . $this->db->escape($data['lastname']) . "',rin_customer = '" . $this->db->escape($data['rin_customer']) . "', email = '" . $this->db->escape($data['email']) . "', telephone = '" . $this->db->escape($data['telephone']) . "', custom_field = '" . $this->db->escape(isset($data['custom_field']) ? json_encode($data['custom_field']) : '') . "', payment_firstname = '" . $this->db->escape($data['payment_firstname']) . "', payment_lastname = '" . $this->db->escape($data['payment_lastname']) . "', payment_company = '" . $this->db->escape($data['payment_company']) . "', payment_address_1 = '" . $this->db->escape($data['payment_address_1']) . "', payment_address_2 = '" . $this->db->escape($data['payment_address_2']) . "', payment_city = '" . $this->db->escape($data['payment_city']) . "', payment_postcode = '" . $this->db->escape($data['payment_postcode']) . "', payment_country = '" . $this->db->escape($data['payment_country']) . "', payment_country_id = '" . (int)$data['payment_country_id'] . "', payment_zone = '" . $this->db->escape($data['payment_zone']) . "', payment_zone_id = '" . (int)$data['payment_zone_id'] . "', payment_address_format = '" . $this->db->escape($data['payment_address_format']) . "', payment_custom_field = '" . $this->db->escape(isset($data['payment_custom_field']) ? json_encode($data['payment_custom_field']) : '') . "', payment_method = '" . $this->db->escape($data['payment_method']) . "', payment_code = '" . $this->db->escape($data['payment_code']) . "', shipping_firstname = '" . $this->db->escape($data['shipping_firstname']) . "', shipping_lastname = '" . $this->db->escape($data['shipping_lastname']) . "', shipping_company = '" . $this->db->escape($data['shipping_company']) . "', shipping_address_1 = '" . $this->db->escape($data['shipping_address_1']) . "', shipping_address_2 = '" . $this->db->escape($data['shipping_address_2']) . "', shipping_city = '" . $this->db->escape($data['shipping_city']) . "', shipping_postcode = '" . $this->db->escape($data['shipping_postcode']) . "', shipping_country = '" . $this->db->escape($data['shipping_country']) . "', shipping_country_id = '" . (int)$data['shipping_country_id'] . "', shipping_zone = '" . $this->db->escape($data['shipping_zone']) . "', shipping_zone_id = '" . (int)$data['shipping_zone_id'] . "', shipping_address_format = '" . $this->db->escape($data['shipping_address_format']) . "', shipping_custom_field = '" . $this->db->escape(isset($data['shipping_custom_field']) ? json_encode($data['shipping_custom_field']) : '') . "', shipping_method = '" . $this->db->escape($data['shipping_method']) . "', shipping_code = '" . $this->db->escape($data['shipping_code']) . "', comment = '" . $this->db->escape($data['comment']) . "', total = '" . (float)$data['total'] . "', affiliate_id = '" . (int)$data['affiliate_id'] . "', commission = '" . (float)$data['commission'] . "', marketing_id = '" . (int)$data['marketing_id'] . "', tracking = '" . $this->db->escape($data['tracking']) . "', language_id = '" . (int)$data['language_id'] . "', currency_id = '" . (int)$data['currency_id'] . "', currency_code = '" . $this->db->escape($data['currency_code']) . "', currency_value = '" . (float)$data['currency_value'] . "', ip = '" . $this->db->escape($data['ip']) . "', forwarded_ip = '" .  $this->db->escape($data['forwarded_ip']) . "', user_agent = '" . $this->db->escape($data['user_agent']) . "', accept_language = '" . $this->db->escape($data['accept_language']) . "', date_added = NOW(), date_modified = NOW()");

		$order_id = $this->db->getLastId();

		// Products
		if (isset($data['products'])) {
			foreach ($data['products'] as $product) {
				$this->db->query("INSERT INTO " . DB_PREFIX . "order_product SET order_id = '" . (int)$order_id . "', product_id = '" . (int)$product['product_id'] . "', name = '" . $this->db->escape($product['name']) . "', model = '" . $this->db->escape($product['model']) . "', quantity = '" . (int)$product['quantity'] . "', price = '" . (float)$product['price'] . "', total = '" . (float)$product['total'] . "', tax = '" . (float)$product['tax'] . "', reward = '" . (int)$product['reward'] . "'");

				$order_product_id = $this->db->getLastId();

				foreach ($product['option'] as $option) {
					$this->db->query("INSERT INTO " . DB_PREFIX . "order_option SET order_id = '" . (int)$order_id . "', order_product_id = '" . (int)$order_product_id . "', product_option_id = '" . (int)$option['product_option_id'] . "', product_option_value_id = '" . (int)$option['product_option_value_id'] . "', name = '" . $this->db->escape($option['name']) . "', `value` = '" . $this->db->escape($option['value']) . "', `type` = '" . $this->db->escape($option['type']) . "'");
				}
			}
		}


		// Totals
		if (isset($data['totals'])) {
			foreach ($data['totals'] as $total) {
				$this->db->query("INSERT INTO " . DB_PREFIX . "order_total SET order_id = '" . (int)$order_id . "', code = '" . $this->db->escape($total['code']) . "', title = '" . $this->db->escape($total['title']) . "', `value` = '" . (float)$total['value'] . "', sort_order = '" . (int)$total['sort_order'] . "'");
			}
		}

		return $order_id;
	}	

	public function getOrderOptions($order_id, $order_product_id) {
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "order_option WHERE order_id = '" . (int)$order_id . "' AND order_product_id = '" . (int)$order_product_id . "'");
		
		return $query->rows;
	}
	
	public function addOrderHistory($order_id, $order_status_id, $comment = '', $notify = false, $override = false) {
		$order_info = $this->getOrder($order_id);
		
		if ($order_info) {

			// If current order status is not processing or complete but new status is processing or complete then commence completing the order
			if (!in_array($order_info['order_status_id'], array_merge($this->config->get('config_processing_status'), $this->config->get('config_complete_status'))) && in_array($order_status_id, array_merge($this->config->get('config_processing_status'), $this->config->get('config_complete_status')))) {
				// Stock subtraction
				$order_products = $this->getOrderProducts($order_id);

				foreach ($order_products as $order_product) {
					$this->db->query("UPDATE " . DB_PREFIX . "product SET quantity = (quantity - " . (int)$order_product['quantity'] . ") WHERE product_id = '" . (int)$order_product['product_id'] . "' AND subtract = '1'");

					$order_options = $this->getOrderOptions($order_id, $order_product['order_product_id']);

					foreach ($order_options as $order_option) {
						$this->db->query("UPDATE " . DB_PREFIX . "product_option_value SET quantity = (quantity - " . (int)$order_product['quantity'] . ") WHERE product_option_value_id = '" . (int)$order_option['product_option_value_id'] . "' AND subtract = '1'");
					}
				}

			}

			// Update the DB with the new statuses
			$this->db->query("UPDATE `" . DB_PREFIX . "order` SET order_status_id = '" . (int)$order_status_id . "', date_modified = NOW() WHERE order_id = '" . (int)$order_id . "'");

			$this->db->query("INSERT INTO " . DB_PREFIX . "order_history SET order_id = '" . (int)$order_id . "', order_status_id = '" . (int)$order_status_id . "', notify = '" . (int)$notify . "', comment = '" . $this->db->escape($comment) . "', date_added = NOW()");

			// If old order status is the processing or complete status but new status is not then commence restock, and remove coupon, voucher and reward history
			if (in_array($order_info['order_status_id'], array_merge($this->config->get('config_processing_status'), $this->config->get('config_complete_status'))) && !in_array($order_status_id, array_merge($this->config->get('config_processing_status'), $this->config->get('config_complete_status')))) {
				// Restock
				$order_products = $this->getOrderProducts($order_id);

				foreach($order_products as $order_product) {
					$this->db->query("UPDATE `" . DB_PREFIX . "product` SET quantity = (quantity + " . (int)$order_product['quantity'] . ") WHERE product_id = '" . (int)$order_product['product_id'] . "' AND subtract = '1'");

					$order_options = $this->getOrderOptions($order_id, $order_product['order_product_id']);

					foreach ($order_options as $order_option) {
						$this->db->query("UPDATE " . DB_PREFIX . "product_option_value SET quantity = (quantity + " . (int)$order_product['quantity'] . ") WHERE product_option_value_id = '" . (int)$order_option['product_option_value_id'] . "' AND subtract = '1'");
					}
				}
			}

			$this->cache->delete('product');
		}
	}
	
	public function getPOpts($product_id) {
		$product_option_data = array();

		$product_option_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_option po LEFT JOIN `" . DB_PREFIX . "option` o ON (po.option_id = o.option_id) LEFT JOIN " . DB_PREFIX . "option_description od ON (o.option_id = od.option_id) WHERE po.product_id = '" . (int)$product_id . "' AND od.language_id = '" . (int)$this->config->get('config_language_id') . "' ORDER BY o.sort_order");

		foreach ($product_option_query->rows as $product_option) {
			$product_option_value_data = array();

			$product_option_value_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_option_value pov LEFT JOIN " . DB_PREFIX . "option_value ov ON (pov.option_value_id = ov.option_value_id) LEFT JOIN " . DB_PREFIX . "option_value_description ovd ON (ov.option_value_id = ovd.option_value_id) WHERE pov.product_id = '" . (int)$product_id . "' AND pov.product_option_id = '" . (int)$product_option['product_option_id'] . "' AND ovd.language_id = '" . (int)$this->config->get('config_language_id') . "' ORDER BY ov.sort_order");

			foreach ($product_option_value_query->rows as $product_option_value) {
				$product_option_value_data[] = array(
					'product_option_value_id' => $product_option_value['product_option_value_id'],
					'option_value_id'         => $product_option_value['option_value_id'],
					'name'                    => $product_option_value['name'],
					'image'                   => $product_option_value['image'],
					'quantity'                => $product_option_value['quantity'],
					'subtract'                => $product_option_value['subtract'],
					'price'                   => $product_option_value['price'],
					'price_prefix'            => $product_option_value['price_prefix'],
					'weight'                  => $product_option_value['weight'],
					'weight_prefix'           => $product_option_value['weight_prefix']
				);
			}

			$product_option_data[] = array(
				'product_option_id'    => $product_option['product_option_id'],
				'product_option_value' => $product_option_value_data,
				'option_id'            => $product_option['option_id'],
                'unit_id'         => $product_option['unit_id'],
				'name'                 => $product_option['name'],
				'type'                 => $product_option['type'],
				'value'                => $product_option['value'],
				'required'             => $product_option['required']
			);
		}

		return $product_option_data;
	}

public function getProductPrice($product_id, $pricing_type, $unit_id) {
    $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_pricing 
                               WHERE product_id = '" . (int)$product_id . "' 
                               AND unit_id = '" . (int)$unit_id . "'");
    $pricing = $query->row;

    switch ($pricing_type) {
        case 'wholesale':
            return $pricing['wholesale_price'];
        case 'half_wholesale':
            return $pricing['half_wholesale_price'];
        case 'custom':
            return $pricing['custom_price'];
        default:
            return $pricing['special_price'] ? $pricing['special_price'] : $pricing['base_price'];
    }
}	
/*
     public function getTotals($shipping_method = '', $payment_method = '') {
        $this->load->model('pos/cart');
        $this->load->language('pos/pos');

        $total_data = array();
        $total = 0;
        $taxes = array();
        $products = $this->model_pos_cart->getProducts();

        // Calculate product totals and taxes
        foreach ($products as $product) {
         $price = $this->getProductPrice($product['product_id'], $pricing_type, $product['unit_id']);

            $product_total = $price * $product['quantity'];
            $total += $product_total;

            if ($product['tax_class_id']) {
                $tax_rates = $this->tax->getRates($price, $product['tax_class_id']);

                foreach ($tax_rates as $tax_rate) {
                    if (!isset($taxes[$tax_rate['tax_rate_id']])) {
                        $taxes[$tax_rate['tax_rate_id']] = ($tax_rate['amount'] * $product['quantity']);
                    } else {
                        $taxes[$tax_rate['tax_rate_id']] += ($tax_rate['amount'] * $product['quantity']);
                    }
                }
            }
        }

        $total_data[] = array(
            'code'       => 'sub_total',
            'title'      => $this->language->get('text_sub_total'),
            'value'      => $total,
            'sort_order' => 1
        );

        // Apply coupon discount if a coupon is stored in the session
        $coupon_discount = 0;
        if (isset($this->session->data['coupon'])) {
            $coupon_info = $this->getCoupon($this->session->data['coupon']);
            if ($coupon_info) {
                $coupon_discount = $this->calculateCouponDiscount($coupon_info, $products, $total);
                $total -= $coupon_discount;

                $total_data[] = array(
                    'code'       => 'coupon',
                    'title'      => sprintf($this->language->get('text_coupon'), $this->session->data['coupon']),
                    'value'      => -$coupon_discount,
                    'sort_order' => 2
                );
            }
        }

        // Calculate shipping cost if a shipping method is provided
        if ($shipping_method) {
            $total_weight = array_sum(array_column($products, 'weight'));
            $shipping_cost = $this->calculateImprovedShippingCost($shipping_method, $total, $total_weight);
            $total += $shipping_cost;
            $total_data[] = array(
                'code'       => 'shipping',
                'title'      => $this->language->get('text_shipping'),
                'value'      => $shipping_cost,
                'sort_order' => 3
            );
        }

        // Calculate total tax
        $tax_total = 0;
        foreach ($taxes as $tax) {
            $tax_total += $tax;
        }

        $total_data[] = array(
            'code'       => 'tax',
            'title'      => $this->language->get('text_tax'),
            'value'      => $tax_total,
            'sort_order' => 4
        );

        // Calculate grand total
        $grand_total = $total + $tax_total;

        $total_data[] = array(
            'code'       => 'total',
            'title'      => $this->language->get('text_total'),
            'value'      => $grand_total,
            'sort_order' => 5
        );

        // Format currency for display
        foreach ($total_data as &$total_item) {
            $total_item['text'] = $this->currency->format($total_item['value'], $this->config->get('config_currency'));
        }

        return array(
            'totals' => $total_data,
            'total'  => $grand_total
        );
    }
*/
    private function calculateImprovedShippingCost($shipping_method, $total, $weight = 0, $geo_zone_id = 0) {
        $shipping = explode('.', $shipping_method);
        
        if (!isset($shipping[0])) {
            return 0;
        }

        $shipping_code = $shipping[0];
        
        // Load shipping method specific settings
        $cost = (float)$this->config->get('shipping_' . $shipping_code . '_cost');
        $tax_class_id = $this->config->get('shipping_' . $shipping_code . '_tax_class_id');
        $geo_zone_id = (int)$this->config->get('shipping_' . $shipping_code . '_geo_zone_id');
        $status = (bool)$this->config->get('shipping_' . $shipping_code . '_status');
        
        // Check if the shipping method is enabled
        if (!$status) {
            return 0;
        }
        
        // Check geo zone
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "zone_to_geo_zone WHERE geo_zone_id = '" . (int)$geo_zone_id . "' AND country_id = '" . (int)$this->config->get('config_country_id') . "' AND (zone_id = '" . (int)$this->config->get('config_zone_id') . "' OR zone_id = '0')");
        
        if ($geo_zone_id && !$query->num_rows) {
            return 0;
        }
        
        // Calculate based on order total
        if ($this->config->get('shipping_' . $shipping_code . '_total') > 0 && $this->config->get('shipping_' . $shipping_code . '_total') > $total) {
            return 0;
        }
        
        // Calculate based on weight
        $weight_cost = 0;
        $weight_rates = explode(',', $this->config->get('shipping_' . $shipping_code . '_weight_rates'));
        foreach ($weight_rates as $weight_rate) {
            $data = explode(':', $weight_rate);
            if ($data[0] >= $weight) {
                if (isset($data[1])) {
                    $weight_cost = $data[1];
                }
                break;
            }
        }
        
        // Final cost calculation
        $shipping_cost = $cost + $weight_cost;
        
        // Apply tax
        if ($tax_class_id) {
            $shipping_cost = $this->tax->calculate($shipping_cost, $tax_class_id, $this->config->get('config_tax'));
        }
        
        return $shipping_cost;
    }






    public function getPaymentMethods() {
        $this->load->model('setting/extension');

        $payment_methods = array();

        $results = $this->model_setting_extension->getInstalled('payment');

        foreach ($results as $result) {
            if ($this->config->get('payment_' . $result . '_status')) {
                $this->load->language('extension/payment/' . $result);

                $payment_methods[] = array(
                    'code'       => $result,
                    'title'      => $this->language->get('heading_title'),
                    'sort_order' => $this->config->get('payment_' . $result . '_sort_order')
                );
            }
        }

        $sort_order = array();

        foreach ($payment_methods as $key => $value) {
            $sort_order[$key] = $value['sort_order'];
        }

        array_multisort($sort_order, SORT_ASC, $payment_methods);

        return $payment_methods;
    }

    public function getShippingMethods() {
        $this->load->model('setting/extension');

        $shipping_methods = array();

        $results = $this->model_setting_extension->getInstalled('shipping');

        foreach ($results as $result) {
            if ($this->config->get('shipping_' . $result . '_status')) {
                $this->load->language('extension/shipping/' . $result);

                $shipping_methods[] = array(
                    'code'       => $result,
                    'title'      => $this->language->get('heading_title'),
                    'sort_order' => $this->config->get('shipping_' . $result . '_sort_order')
                );
            }
        }

        $sort_order = array();

        foreach ($shipping_methods as $key => $value) {
            $sort_order[$key] = $value['sort_order'];
        }

        array_multisort($sort_order, SORT_ASC, $shipping_methods);

        return $shipping_methods;
    }

public function addToCart($product_id, $quantity, $options, $unit_id, $pricing_type, $branch_id) {
    $this->load->model('pos/cart');
    return $this->model_pos_cart->add($product_id, $quantity, $options, $unit_id, $pricing_type, $branch_id);
}

    public function addCustomer($data) {
        $this->db->query("INSERT INTO " . DB_PREFIX . "customer SET 
            customer_group_id = '" . (int)$data['customer_group_id'] . "', 
            store_id = '" . (int)$this->config->get('config_store_id') . "', 
            firstname = '" . $this->db->escape($data['firstname']) . "', 
            lastname = '" . $this->db->escape($data['lastname']) . "', 
            email = '" . $this->db->escape($data['email']) . "', 
            telephone = '" . $this->db->escape($data['telephone']) . "', 
            fax = '" . $this->db->escape($data['fax']) . "', 
            custom_field = '" . $this->db->escape(json_encode($data['custom_field'])) . "', 
            salt = '" . $this->db->escape($salt = token(9)) . "', 
            password = '" . $this->db->escape(sha1($salt . sha1($salt . sha1($data['password'])))) . "', 
            status = '1', 
            date_added = NOW()");

        $customer_id = $this->db->getLastId();

        $this->db->query("INSERT INTO " . DB_PREFIX . "address SET 
            customer_id = '" . (int)$customer_id . "', 
            firstname = '" . $this->db->escape($data['firstname']) . "', 
            lastname = '" . $this->db->escape($data['lastname']) . "', 
            company = '" . $this->db->escape($data['company']) . "', 
            address_1 = '" . $this->db->escape($data['address_1']) . "', 
            address_2 = '" . $this->db->escape($data['address_2']) . "', 
            city = '" . $this->db->escape($data['city']) . "', 
            postcode = '" . $this->db->escape($data['postcode']) . "', 
            country_id = '" . (int)$data['country_id'] . "', 
            zone_id = '" . (int)$data['zone_id'] . "'");

        return $customer_id;
    }

    public function searchCustomers($query) {
        $sql = "SELECT customer_id, CONCAT(firstname, ' ', lastname) AS name, email FROM " . DB_PREFIX . "customer 
                WHERE CONCAT(firstname, ' ', lastname) LIKE '%" . $this->db->escape($query) . "%' 
                OR email LIKE '%" . $this->db->escape($query) . "%'";

        $query = $this->db->query($sql);

        return $query->rows;
    }





}
