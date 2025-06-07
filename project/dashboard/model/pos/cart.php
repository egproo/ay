<?php
class ModelPosCart extends Model {
    public function __construct($registry) {
        parent::__construct($registry);
        $this->load->model('catalog/product');
    }  
    
// دالة لإضافة منتج إلى السلة
public function add($product_id, $quantity = 1.0, $option = array(), $unit_id = 0, $pricing_type = 'retail', $branch_id = 0) {
    $this->load->model('catalog/product');
    $this->load->model('pos/pos');
    
    $product_info = $this->model_catalog_product->getProduct($product_id);

    if ($product_info) {
        $price = $this->model_pos_pos->getProductPrice($product_id, $pricing_type, $unit_id);
        
        $key = (int)$product_id . ':';
        if ($option) {
            $key .= base64_encode(serialize($option));
        }
        $key .= ':' . (int)$unit_id;

        if ((float)$quantity && ((float)$quantity > 0)) {
            if (!isset($this->session->data['cart'])) {
                $this->session->data['cart'] = array();
            }

            if (!isset($this->session->data['cart'][$key])) {
                $this->session->data['cart'][$key] = array(
                    'product_id' => $product_id,
                    'quantity' => (float)$quantity,
                    'option' => $option,
                    'unit_id' => $unit_id,
                    'price' => $price,
                    'total' => $price * $quantity
                );
            } else {
                $this->session->data['cart'][$key]['quantity'] += (float)$quantity;
                $this->session->data['cart'][$key]['total'] = $this->session->data['cart'][$key]['price'] * $this->session->data['cart'][$key]['quantity'];
            }

            // تحديث تكامل المعاملات
            if (isset($this->session->data['active_shift'])) {
                $this->load->model('pos/transaction');
                $this->session->data['cart_shift_id'] = $this->session->data['active_shift']['shift_id'];
            }

            // Add to inventory history
            $this->addInventoryHistory($product_id, $unit_id, $quantity, 'subtract','');

            return true;
        }
    }

    return false;
}

// تعديل دالة clear لتتعامل مع نظام المناوبات
public function clear() {
    $this->session->data['cart'] = array();
    unset($this->session->data['coupon']);
    unset($this->session->data['cart_shift_id']);
}

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


private function getProductStock($product_id, $unit_id, $branch_id) {
    $query = $this->db->query("SELECT quantity FROM " . DB_PREFIX . "product_inventory 
                               WHERE product_id = '" . (int)$product_id . "' 
                               AND unit_id = '" . (int)$unit_id . "' 
                               AND branch_id = '" . (int)$branch_id . "'");
    
    $stock = $query->num_rows ? (float)$query->row['quantity'] : 0;
    
    error_log("Stock Query for Product ID: " . $product_id . ", Unit ID: " . $unit_id . ", Branch ID: " . $branch_id . ", Stock: " . $stock);
    
    return $stock;
    
}

public function getProducts() {
    $product_data = array();

    foreach ($this->session->data['cart'] as $key => $item) {
        $product = explode(':', $key);
        $product_id = (int)$product[0];
        $unit_id = isset($product[2]) ? (int)$product[2] : 0;
        //$options = isset($product[1]) ? unserialize(base64_decode($product[1])) : array();
        $options = isset($product[1]) && !empty($product[1]) ? unserialize(base64_decode($product[1])) : array();
    error_log("options IN CARTS : " . json_encode($options));

        $product_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product p 
                                           LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id) 
                                           WHERE p.product_id = '" . (int)$product_id . "' 
                                           AND pd.language_id = '" . (int)$this->config->get('config_language_id') . "' 
                                           AND p.date_available <= NOW() 
                                           AND p.status = '1'");

        if ($product_query->num_rows) {
            $option_price = 0;
            $option_data = array();
            $option_name = '';

            foreach ($options as $product_option_id => $option_value) {
                $product_options = $this->getProductOptions($product_id);

                foreach ($product_options as $product_option) {
                    if ($product_option['product_option_id'] == $product_option_id) {
                        foreach ($product_option['product_option_value'] as $option_value_data) {
                            if ($option_value_data['product_option_value_id'] == $option_value) {
                                if ($option_value_data['price_prefix'] == '+') {
                                    $option_price += $option_value_data['price'];
                                } elseif ($option_value_data['price_prefix'] == '-') {
                                    $option_price -= $option_value_data['price'];
                                } elseif ($option_value_data['price_prefix'] == '=') {
                                    $option_price = $option_value_data['price'];                                    
                                }

                                $option_data[] = array(
                                    'product_option_id'       => $product_option_id,
                                    'product_option_value_id' => $option_value,
                                    'option_id'               => $product_option['option_id'],
                                    'option_value_id'         => $option_value_data['option_value_id'],
                                    'name'                    => $product_option['name'],
                                    'value'                   => $option_value_data['name'],
                                    'type'                    => $product_option['type'],
                                    'price'                   => $option_value_data['price'],
                                    'price_prefix'            => $option_value_data['price_prefix']
                                );

                                $option_name .= $product_option['name'] . ': ' . $option_value_data['name'] . ', ';
                            }
                        }
                    }
                }
            }

            $pricing_type = isset($this->session->data['pricing_type']) ? $this->session->data['pricing_type'] : 'retail';
            $product_pricing = $this->getProductPricing($product_id, $unit_id);
            $base_price = $product_pricing[$pricing_type . '_price'] ?? $product_pricing['base_price'] ?? 0;
            $final_price = $option_price ? $option_price + $base_price : $base_price;

            $tax_class_id = $product_query->row['tax_class_id'];
            $taxes = $this->tax->getTax($final_price, $tax_class_id);
            $price_with_tax = $final_price + $taxes;

            $quantity = $item['quantity'];
            $available_quantity = $this->getProductAvailableQuantity($product_id, $unit_id);

            if ($available_quantity < $quantity) {
                $quantity = $available_quantity;
                $this->session->data['cart'][$key]['quantity'] = $quantity;
            }

            $product_data[] = array(
                'key'         => $key,
                'product_id'  => $product_id,
                'name'        => $product_query->row['name'] . (!empty($option_name) ? ' (' . rtrim($option_name, ', ') . ')' : ''). (!empty($this->getUnitName($unit_id)) ? ' (' . rtrim($this->getUnitName($unit_id), ', ') . ')' : ''),
                'model'       => $product_query->row['model'],
                'shipping'    => $product_query->row['shipping'],
                'image'       => $product_query->row['image'],
                'option'      => $option_data,
                'quantity'    => $quantity,
                'minimum'     => $product_query->row['minimum'],
                'subtract'    => $product_query->row['subtract'],
                'price'       => $final_price,
                'price_with_tax' => $price_with_tax,
                'total'       => $price_with_tax * $quantity,
                'tax_class_id'=> $product_query->row['tax_class_id'],
                'weight'      => $product_query->row['weight'],
                'weight_class_id' => $product_query->row['weight_class_id'],
                'length'      => $product_query->row['length'],
                'width'       => $product_query->row['width'],
                'height'      => $product_query->row['height'],
                'length_class_id' => $product_query->row['length_class_id'],
                'unit_id'     => $unit_id,
                'unit_name'   => $this->getUnitName($unit_id),
                'unit'        => $this->getUnitName($unit_id)
            );
        } else {
            $this->remove($key);
        }
    }

    return $product_data;
}
    
    


    
private function getPriceByType($product_id, $pricing_type, $unit_id) {
    $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_pricing WHERE product_id = '" . (int)$product_id . "' AND unit_id = '" . (int)$unit_id . "'");
    if ($query->num_rows) {
        $row = $query->row;
        switch ($pricing_type) {
            case 'wholesale':
                return isset($row['wholesale_price']) && $row['wholesale_price'] > 0 ? $row['wholesale_price'] : $row['base_price'];
            case 'half_wholesale':
                return isset($row['half_wholesale_price']) && $row['half_wholesale_price'] > 0 ? $row['half_wholesale_price'] : $row['base_price'];
            case 'custom':
                return isset($row['custom_price']) && $row['custom_price'] > 0 ? $row['custom_price'] : $row['base_price'];
            case 'retail':
            default:
                return isset($row['special_price']) && $row['special_price'] > 0 ? $row['special_price'] : $row['base_price'];
        }
    }
    return 0;
}

    public function applyCoupon($coupon_code) {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "coupon WHERE code = '" . $this->db->escape($coupon_code) . "' AND status = '1' AND ((date_start = '0000-00-00' OR date_start < NOW()) AND (date_end = '0000-00-00' OR date_end > NOW()))");

        if ($query->num_rows) {
            $this->session->data['coupon'] = $coupon_code;
            return $query->row;
        }

        return false;
    }

    public function removeCoupon() {
        unset($this->session->data['coupon']);
    }
	
	
	public function getProductOptions($product_id) {
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
				'name'                 => $product_option['name'],
				'unit_id'                 => $product_option['unit_id'],
				'type'                 => $product_option['type'],
				'value'                => $product_option['value'],
				'required'             => $product_option['required']
			);
		}

		return $product_option_data;
	}
	
    public function getTaxes() {
        $tax_data = array();

        foreach ($this->session->data['cart'] as $key => $quantity) {
            $product = explode(':', $key);
            $product_id = (int)$product[0];

            $product_query = $this->db->query("SELECT price, tax_class_id FROM " . DB_PREFIX . "product WHERE product_id = '" . (int)$product_id . "'");

            if ($product_query->num_rows) {
                $price = $product_query->row['price'];
                $tax_class_id = $product_query->row['tax_class_id'];

                if ($tax_class_id) {
                    $tax_rates = $this->tax->getRates($price, $tax_class_id);

                    foreach ($tax_rates as $tax_rate) {
                        if (!isset($tax_data[$tax_rate['tax_rate_id']])) {
                            $tax_data[$tax_rate['tax_rate_id']] = ($tax_rate['amount'] * $quantity);
                        } else {
                            $tax_data[$tax_rate['tax_rate_id']] += ($tax_rate['amount'] * $quantity);
                        }
                    }
                }
            }
        }

        return $tax_data;
    }

    // دالة لتحديث كمية منتج في السلة
    public function update($key, $quantity) {
        if ((int)$quantity && ((int)$quantity > 0)) {
            $this->session->data['cart'][$key] = (int)$quantity;
        } else {
            $this->remove($key);
        }
    }

    // دالة لإزالة منتج من السلة
    public function remove($key) {
        if (isset($this->session->data['cart'][$key])) {
            unset($this->session->data['cart'][$key]);
        }
    }


private function getProductAvailableQuantity($product_id, $unit_id) {
    // استعلام لجلب الكمية المتاحة من المخزون للمنتج بناءً على الوحدة
    $query = $this->db->query("SELECT quantity_available FROM " . DB_PREFIX . "product_inventory WHERE product_id = '" . (int)$product_id . "' AND unit_id = '" . (int)$unit_id . "'");

    if ($query->num_rows) {
        return (float)$query->row['quantity_available'];
    } else {
        // في حالة عدم وجود سجل للوحدة المحددة، يمكن أن نرجع 0 كمخزون متاح
        return 0;
    }
}



private function getProductPricing($product_id, $unit_id) {
    $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_pricing WHERE product_id = '" . (int)$product_id . "' AND unit_id = '" . (int)$unit_id . "'");
    return $query->row;
}


private function getUnitName($unit_id) {
    $query = $this->db->query("SELECT CONCAT(desc_en, ' - ', desc_ar) AS unit_name FROM " . DB_PREFIX . "unit WHERE unit_id = '" . (int)$unit_id . "'");
    return $query->row ? $query->row['unit_name'] : '';
}


}
