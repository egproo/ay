<?php
class ControllerPosPos extends Controller {
    protected function checkActiveShift() {
    $this->load->model('pos/shift');
    $active_shift = $this->model_pos_shift->getActiveShiftByUser($this->user->getId());
    
    if (!$active_shift && $this->config->get('pos_require_shift')) {
        return false;
    }
    
    if ($active_shift) {
        $this->session->data['active_shift'] = $active_shift;
    }
    
    return true;
   }


    public function index() {
        $this->load->language('pos/pos');
        $this->load->model('pos/pos');

    // التحقق من وجود مناوبة نشطة إذا كان الإعداد يتطلب ذلك
    if ($this->config->get('pos_require_shift') && !$this->checkActiveShift()) {
        $this->session->data['error'] = $this->language->get('error_no_active_shift');
        $this->response->redirect($this->url->link('pos/shift/start', 'user_token=' . $this->session->data['user_token'], true));
    }


        $this->document->setTitle($this->language->get('heading_title'));

        $data['heading_title'] = $this->language->get('heading_title');

        $data['text_select_category'] = $this->language->get('text_select_category');
        $data['text_search_products'] = $this->language->get('text_search_products');
        $data['text_search_customers'] = $this->language->get('text_search_customers');
        $data['text_cart'] = $this->language->get('text_cart');
        $data['text_shipping_methods'] = $this->language->get('text_shipping_methods');
        $data['text_payment_methods'] = $this->language->get('text_payment_methods');
        $data['text_add_new_customer'] = $this->language->get('text_add_new_customer');
        $data['text_add_new_customer_tooltip'] = $this->language->get('text_add_new_customer_tooltip');
        $data['text_apply_coupon_tooltip'] = $this->language->get('text_apply_coupon_tooltip');
        $data['text_suspend_sale_tooltip'] = $this->language->get('text_suspend_sale_tooltip');
        $data['text_resume_sale_tooltip'] = $this->language->get('text_resume_sale_tooltip');
        $data['text_void_sale_tooltip'] = $this->language->get('text_void_sale_tooltip');
        $data['text_complete_sale_tooltip'] = $this->language->get('text_complete_sale_tooltip');
        $data['text_enter_coupon'] = $this->language->get('text_enter_coupon');
        $data['text_confirm_void'] = $this->language->get('text_confirm_void');
        $data['text_no_customers_found'] = $this->language->get('text_no_customers_found');
        $data['text_selected_customer'] = $this->language->get('text_selected_customer');
        $data['text_added_customer'] = $this->language->get('text_added_customer');
        $data['text_national_id'] = $this->language->get('text_national_id');
        $data['text_enter_national_id'] = $this->language->get('text_enter_national_id');
        $data['text_tax_id'] = $this->language->get('text_tax_id');
        $data['text_enter_tax_id'] = $this->language->get('text_enter_tax_id');
        $data['text_change_quantity'] = $this->language->get('text_change_quantity');
        $data['text_remove_item'] = $this->language->get('text_remove_item');

        $data['button_add_new_customer'] = $this->language->get('button_add_new_customer');
        $data['button_add'] = $this->language->get('button_add');
        $data['button_cancel'] = $this->language->get('button_cancel');
        $data['button_apply_coupon'] = $this->language->get('button_apply_coupon');
        $data['button_suspend_sale'] = $this->language->get('button_suspend_sale');
        $data['button_resume_sale'] = $this->language->get('button_resume_sale');
        $data['button_void_sale'] = $this->language->get('button_void_sale');
        $data['button_complete_sale'] = $this->language->get('button_complete_sale');
        $data['button_add_to_cart'] = $this->language->get('button_add_to_cart');
        $data['text_select_options'] = $this->language->get('text_select_options');

        $data['current_branch'] = $this->model_pos_pos->getCurrentUserBranch();
        $data['is_admin'] = $this->user->getGroupId() == 1;        
        $data['pricing_types'] = array(
            'retail' => $this->language->get('text_retail'),
            'wholesale' => $this->language->get('text_wholesale'),//special then base
            'half_wholesale' => $this->language->get('text_half_wholesale'),
            'custom' => $this->language->get('text_custom')
        );
        
        $data['user_token'] = $this->session->data['user_token'];

        $this->load->model('catalog/category');
        $data['categories'] = $this->model_catalog_category->getCategories();

        $this->load->model('localisation/zone');
        $data['zones'] = $this->model_localisation_zone->getZonesByCountryId(63);

        $this->load->model('customer/customer_group');
        $data['customer_groups'] = $this->model_customer_customer_group->getCustomerGroups();

        $data['shipping_methods'] = $this->model_pos_pos->getShippingMethods();

        $data['payment_methods'] = $this->model_pos_pos->getPaymentMethods();

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('pos/pos', $data));
    }


public function cleanAllSuspendedCarts() {
    $this->load->language('pos/pos');
    $json = array();
    if (isset($this->session->data['suspended_carts'])) {
        $this->session->data['suspended_carts'] = array();
    }



    $json['success'] = $this->language->get('text_suspend_success');

    $this->response->addHeader('Content-Type: application/json');
    $this->response->setOutput(json_encode($json));
}
public function cleanSuspendedCart() {
    $this->load->language('pos/pos');
    $json = array();
    $suspend_id = $this->request->post['suspend_id'];
    if (isset($this->session->data['suspended_carts'][$suspend_id])) {
         unset($this->session->data['suspended_carts'][$suspend_id]);
    }

    $json['success'] = $this->language->get('text_suspend_success');

    $this->response->addHeader('Content-Type: application/json');
    $this->response->setOutput(json_encode($json));
}


public function resumeSale() {
    $this->load->language('pos/pos');
    $json = array();

    $suspend_id = $this->request->post['suspend_id'];
    if (isset($this->session->data['suspended_carts'][$suspend_id])) {
        $suspended_cart = $this->session->data['suspended_carts'][$suspend_id];

        $this->load->model('pos/pos');
        $this->load->model('pos/cart');
        $this->model_pos_cart->clear();

        foreach ($suspended_cart['products'] as $product) {
            // تحويل الخيارات إلى الصيغة المطلوبة
            $processed_options = array();
            if (!empty($product['option'])) {
                foreach ($product['option'] as $option) {
                    if (isset($option['product_option_id'], $option['product_option_value_id'])) {
                        $processed_options[(string)$option['product_option_id']] = (string)$option['product_option_value_id'];
                    }
                }
            }

            // إضافة المنتج إلى السلة مع الخيارات بالصيغة الصحيحة
            $this->model_pos_cart->add($product['product_id'], $product['quantity'], $processed_options, $product['unit_id']);
        }

        $this->session->data['customer_id'] = $suspended_cart['customer_id'];
        $this->session->data['coupon'] = $suspended_cart['coupon'];
        $this->session->data['payment_method'] = array('title' => $suspended_cart['payment_method']);
        $this->session->data['shipping_method'] = array('title' => $suspended_cart['shipping_method']);

        unset($this->session->data['suspended_carts'][$suspend_id]);

        $json['success'] = $this->language->get('text_resume_success');
        $products = $this->model_pos_cart->getProducts();
        $totals = $this->model_pos_pos->getTotals();

        // تمرير معلومات السلة بعد الإضافة
        $json['products'] = $products;
        $json['totals'] = $totals['totals'];
        $json['total'] = $totals['total'];
        $json['cart'] = array(
            'products' => $products,
            'total' => $totals['total']
        );
    } else {
        $json['error'] = $this->language->get('error_resume');
    }

    $this->response->addHeader('Content-Type: application/json');
    $this->response->setOutput(json_encode($json));
}



public function getSuspendedCarts() {
    $this->load->language('pos/pos');
    $this->load->model('pos/pos');
    $this->load->model('customer/customer');

    $json = array();

    // التحقق من صلاحيات المستخدم (يمكنك تعديل هذا حسب نظام الصلاحيات الخاص بك)
    if (!$this->user->hasPermission('modify', 'pos/pos')) {
        $json['error'] = $this->language->get('error_permission');
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
        return;
    }

    $suspended_carts = array();

    if (isset($this->session->data['suspended_carts'])) {
        foreach ($this->session->data['suspended_carts'] as $suspend_id => $cart) {
            $total = 0;
            $product_count = 0;

            foreach ($cart['products'] as $product) {
                $total += $product['total'];
                $product_count += $product['quantity'];
            }

            $customer_info = $this->model_customer_customer->getCustomer($cart['customer_id']);
            $customer_name = $customer_info ? $customer_info['firstname'] . ' ' . $customer_info['lastname'] : $this->language->get('text_guest');

            $suspended_carts[] = array(
                'id' => $suspend_id,
                'customer_name' => $customer_name,
                'total' => $this->currency->format($total, $this->config->get('config_currency')),
                'product_count' => $product_count,
                'date_added' => isset($cart['date_added']) ? date($this->language->get('date_format_short'), strtotime($cart['date_added'])) : '',
            );
        }
    }

    $json['suspended_carts'] = $suspended_carts;

    $this->response->addHeader('Content-Type: application/json');
    $this->response->setOutput(json_encode($json));
}


public function checkBarcode() {
    $this->load->language('pos/pos');
    $this->load->model('pos/pos');
    
    $json = array('success' => false);
    
    if (isset($this->request->post['barcode']) && isset($this->request->post['pricing_type'])) {
        $barcode = $this->request->post['barcode'];
        $pricing_type = $this->request->post['pricing_type'];
        $branch_id = $this->user->getBranchId();
        
        $product_info = $this->model_pos_pos->getProductByBarcode($barcode);
        
        if ($product_info) {
            $product_options = $this->model_pos_pos->getProductOptions($product_info['product_id']);
            
            if (empty($product_options) || isset($product_info['option_value_id'])) {
                $option = isset($product_info['option_value_id']) ? array($product_info['option_id'] => $product_info['option_value_id']) : array();
                
                $result = $this->model_pos_pos->addToCart(
                    $product_info['product_id'],
                    1,
                    $option,
                    $product_info['unit_id'],
                    $pricing_type,
                    $branch_id
                );
                
                if ($result) {
                    $json['success'] = true;
                    $json['message'] = $this->language->get('text_success');
                    $json['products'] = $this->model_pos_cart->getProducts();
                    $totals = $this->model_pos_pos->getTotals();
                    $json['totals'] = $totals['totals'];
                    $json['total'] = $totals['total'];
                } else {
                    $json['message'] = $this->language->get('error_add_to_cart');
                }
            } else {
                $json['product_id'] = $product_info['product_id'];
                $json['options'] = $product_options;
                $json['default_unit_id'] = $product_info['unit_id'];
                $json['message'] = $this->language->get('text_select_options');
            }
        } else {
            $json['message'] = $this->language->get('error_barcode_not_found');
        }
    } else {
        $json['message'] = $this->language->get('error_invalid_input');
    }

    $this->response->addHeader('Content-Type: application/json');
    $this->response->setOutput(json_encode($json));
}
public function searchProducts() {
    $this->load->model('pos/pos');
    $this->load->model('tool/image');
    
    $json = array();

    $query = isset($this->request->get['query']) ? $this->request->get['query'] : '';
    $category_id = isset($this->request->get['category_id']) ? $this->request->get['category_id'] : '';
    $pricing_type = isset($this->request->get['pricing_type']) ? $this->request->get['pricing_type'] : 'retail';

    $branch_id = ($this->user->getGroupId() == 1) ? 0 : $this->user->getBranchId();

    $results = $this->model_pos_pos->searchProducts($query, $category_id, $pricing_type, $branch_id);

    foreach ($results as $result) {
        $product_options = $this->model_pos_pos->getProductOptions($result['product_id']);
        $json[] = array(
            'product_id' => $result['product_id'],
            'name'       => $result['name'],
            'prices'      => $result['prices'],
            'image'      => $this->model_tool_image->resize($result['image'], 100, 100),
            'stock'      => $result['stock'],
            'has_options' => $result['has_options'],
            'options'    => $product_options,            
            'units'      => $result['units']
        );
    }

    $this->response->addHeader('Content-Type: application/json');
    $this->response->setOutput(json_encode($json));
}
public function getProductOptions() {
    $this->load->model('pos/pos');
    
    $json = array();
    
    if (isset($this->request->get['product_id'])) {
        $product_id = (int)$this->request->get['product_id'];
    } else {
        $product_id = 0;
    }

    // جلب جميع الخيارات
    $product_options = $this->model_pos_pos->getProductOptions($product_id);
    // جلب جميع الوحدات
    $product_units = $this->model_pos_pos->getProductUnits($product_id);
    // جلب الوحدة الأساسية
    $default_unit_id = $this->model_pos_pos->getProductBaseUnit($product_id);

    if ($product_options || $product_units) {
        $json['success'] = true;
        $json['options'] = $product_options;
        $json['units'] = $product_units;
        $json['default_unit_id'] = $default_unit_id;
    } else {
        $json['error'] = $this->language->get('error_no_options');
    }

    $this->response->addHeader('Content-Type: application/json');
    $this->response->setOutput(json_encode($json));
}


public function getTotals() {
    $this->load->language('pos/pos');
    $this->load->model('pos/pos');
    $json = array();

    $shipping_method = isset($this->request->get['shipping_method']) ? $this->request->get['shipping_method'] : '';
    $payment_method = isset($this->request->get['payment_method']) ? $this->request->get['payment_method'] : '';

    // حساب الإجماليات بما في ذلك تكلفة الشحن والدفع
    $totals = $this->model_pos_pos->getTotals($shipping_method, $payment_method);

    $json['totals'] = $totals['totals'];
    $json['total'] = $totals['total'];

    $this->response->addHeader('Content-Type: application/json');
    $this->response->setOutput(json_encode($json));
}
public function getCart() {
    $this->load->language('pos/pos');
    $this->load->model('pos/pos');
    $this->load->model('pos/cart');

    $json = array();

    // الحصول على المنتجات الموجودة في السلة
    $products = $this->model_pos_cart->getProducts();
    $json['products'] = $products ? $products : array();

    // الحصول على الاجماليات
    $totals = $this->model_pos_pos->getTotals();
    $json['totals'] = isset($totals['totals']) ? $totals['totals'] : array();
    $json['total'] = isset($totals['total']) ? $totals['total'] : '0.00';

    $this->response->addHeader('Content-Type: application/json');
    $this->response->setOutput(json_encode($json));
}

public function updateCart() {
    $json = array();
    $this->load->model('pos/pos');
    $this->load->model('pos/cart');
    $this->load->language('pos/pos');

    if (isset($this->request->post['key']) && isset($this->request->post['quantity'])) {
        $key = $this->request->post['key'];
        $quantity = (int)$this->request->post['quantity'];

        // قم بتسجيل البيانات للتأكد من استلامها بشكل صحيح

        $this->model_pos_cart->update($key, $quantity);

        $json['success'] = $this->language->get('text_success_update');
        
        // تمرير معلومات العربة بعد التحديث
        $json['products'] = $this->model_pos_cart->getProducts();
        $totals = $this->model_pos_pos->getTotals(); 
        $json['totals'] = $totals['totals'];
        $json['total'] = $totals['total'];
    } else {
        $json['error'] = $this->language->get('error_update');
    }

    $this->response->addHeader('Content-Type: application/json');
    $this->response->setOutput(json_encode($json));
}


public function removeFromCart() {
    $json = array();
    $this->load->model('pos/pos');
    $this->load->model('pos/cart');
    $this->load->language('pos/pos');
    if (isset($this->request->post['key'])) {
        $key = $this->request->post['key'];

        $this->model_pos_cart->remove($key);

        $json['success'] = $this->language->get('text_success_remove');

        $this->load->model('pos/pos');
        $json['products'] = $this->model_pos_cart->getProducts();
        $totals = $this->model_pos_pos->getTotals(); // Ensure correct call to getTotals method
        $json['totals'] = $totals['totals'];
        $json['total'] = $totals['total'];
    } else {
        $json['error'] = $this->language->get('error_remove');
    }

    $this->response->addHeader('Content-Type: application/json');
    $this->response->setOutput(json_encode($json));
}






    public function applyCoupon() {
        $json = array();

        // Loading model
        $this->load->model('pos/pos');

        // Checking if coupon code is provided
        if (isset($this->request->post['coupon'])) {
            // Getting coupon information
            $coupon_info = $this->model_pos_pos->getCoupon($this->request->post['coupon']);

            // Validating coupon information
            if ($coupon_info) {
                $this->session->data['coupon'] = $this->request->post['coupon'];
                $json['success'] = $this->language->get('text_success_coupon');
        $json['products'] = $this->model_pos_cart->getProducts();
        $totals = $this->model_pos_pos->getTotals(); 
        $json['totals'] = $totals['totals'];
        $json['total'] = $totals['total'];				
            } else {
                $json['error'] = $this->language->get('error_coupon');
            }
        }

        // Setting response
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }


/*
public function completeSale() {
    $json = array();
    $this->load->language('pos/pos');
    $this->load->model('pos/pos');
    $this->load->model('pos/cart');
    $this->load->model('sale/order');

    if (isset($this->request->post['payment_method']) && isset($this->request->post['shipping_method'])) {
        $payment_method = $this->request->post['payment_method'];
        $shipping_method = $this->request->post['shipping_method'];

        $order_data = array();
        $order_data['invoice_prefix'] = $this->config->get('config_invoice_prefix');
        $order_data['store_id'] = $this->config->get('config_store_id');
        $order_data['store_name'] = $this->config->get('config_name');
        if ($order_data['store_id']) {
            $order_data['store_url'] = $this->config->get('config_url');
        } else {
            $order_data['store_url'] = HTTP_CATALOG;
        }

        if (isset($this->session->data['customer_id'])) {
            $this->load->model('customer/customer');
            $customer_info = $this->model_customer_customer->getCustomer($this->session->data['customer_id']);
            $order_data['customer_id'] = $customer_info['customer_id'];
            $order_data['customer_group_id'] = $customer_info['customer_group_id'];
            $order_data['firstname'] = $customer_info['firstname'];
            $order_data['lastname'] = $customer_info['lastname'];
            $order_data['email'] = $customer_info['email'];
            $order_data['telephone'] = $customer_info['telephone'];
            $order_data['fax'] = $customer_info['fax'];
        } else {
            $order_data['customer_id'] = 0;
            $order_data['customer_group_id'] = $this->config->get('config_customer_group_id');
            $order_data['firstname'] = '';
            $order_data['lastname'] = '';
            $order_data['email'] = '';
            $order_data['telephone'] = '';
            $order_data['fax'] = '';
        }

        $order_data['payment_firstname'] = $this->session->data['payment_address']['firstname'];
        $order_data['payment_lastname'] = $this->session->data['payment_address']['lastname'];
        $order_data['payment_company'] = $this->session->data['payment_address']['company'];
        $order_data['payment_address_1'] = $this->session->data['payment_address']['address_1'];
        $order_data['payment_address_2'] = $this->session->data['payment_address']['address_2'];
        $order_data['payment_city'] = $this->session->data['payment_address']['city'];
        $order_data['payment_postcode'] = $this->session->data['payment_address']['postcode'];
        $order_data['payment_country'] = $this->session->data['payment_address']['country'];
        $order_data['payment_country_id'] = $this->session->data['payment_address']['country_id'];
        $order_data['payment_zone'] = $this->session->data['payment_address']['zone'];
        $order_data['payment_zone_id'] = $this->session->data['payment_address']['zone_id'];
        $order_data['payment_address_format'] = $this->session->data['payment_address']['address_format'];
        $order_data['payment_custom_field'] = $this->session->data['payment_address']['custom_field'];

        $order_data['payment_method'] = $payment_method;
        $order_data['payment_code'] = $payment_method;

        if ($shipping_method) {
            $order_data['shipping_firstname'] = $this->session->data['shipping_address']['firstname'];
            $order_data['shipping_lastname'] = $this->session->data['shipping_address']['lastname'];
            $order_data['shipping_company'] = $this->session->data['shipping_address']['company'];
            $order_data['shipping_address_1'] = $this->session->data['shipping_address']['address_1'];
            $order_data['shipping_address_2'] = $this->session->data['shipping_address']['address_2'];
            $order_data['shipping_city'] = $this->session->data['shipping_address']['city'];
            $order_data['shipping_postcode'] = $this->session->data['shipping_address']['postcode'];
            $order_data['shipping_zone'] = $this->session->data['shipping_address']['zone'];
            $order_data['shipping_zone_id'] = $this->session->data['shipping_address']['zone_id'];
            $order_data['shipping_country'] = $this->session->data['shipping_address']['country'];
            $order_data['shipping_country_id'] = $this->session->data['shipping_address']['country_id'];
            $order_data['shipping_address_format'] = $this->session->data['shipping_address']['address_format'];
            $order_data['shipping_custom_field'] = $this->session->data['shipping_address']['custom_field'];

            $order_data['shipping_method'] = $shipping_method;
            $order_data['shipping_code'] = $shipping_method;
        } else {
            $order_data['shipping_method'] = '';
            $order_data['shipping_code'] = '';
        }

        $order_data['products'] = array();

        foreach ($this->model_pos_cart->getProducts() as $product) {
            $option_data = array();

            foreach ($product['option'] as $option) {
                $option_data[] = array(
                    'product_option_id'       => $option['product_option_id'],
                    'product_option_value_id' => $option['product_option_value_id'],
                    'option_id'               => $option['option_id'],
                    'option_value_id'         => $option['option_value_id'],
                    'name'                    => $option['name'],
                    'value'                   => $option['value'],
                    'type'                    => $option['type']
                );
            }

            $order_data['products'][] = array(
                'product_id' => $product['product_id'],
                'name'       => $product['name'],
                'model'      => $product['model'],
                'option'     => $option_data,
                'download'   => $product['download'],
                'quantity'   => $product['quantity'],
                'subtract'   => $product['subtract'],
                'price'      => $product['price'],
                'total'      => $product['total'],
                'tax'        => $product['tax'],
                'reward'     => $product['reward']
            );
        }

        $order_data['vouchers'] = array();

        if (!empty($this->session->data['vouchers'])) {
            foreach ($this->session->data['vouchers'] as $voucher) {
                $order_data['vouchers'][] = array(
                    'description'      => $voucher['description'],
                    'code'             => substr(md5(mt_rand()), 0, 10),
                    'to_name'          => $voucher['to_name'],
                    'to_email'         => $voucher['to_email'],
                    'from_name'        => $voucher['from_name'],
                    'from_email'       => $voucher['from_email'],
                    'voucher_theme_id' => $voucher['voucher_theme_id'],
                    'message'          => $voucher['message'],
                    'amount'           => $voucher['amount']
                );
            }
        }

        $order_data['comment'] = '';
        $totals = $this->model_pos_cart->getTotals($shipping_method, $payment_method);
        $order_data['total'] = $totals['total'];

        if (isset($this->session->data['affiliate_id'])) {
            $subtotal = $this->model_pos_cart->getSubTotal();

            $affiliate_info = $this->model_account_customer->getAffiliate($this->session->data['affiliate_id']);

            if ($affiliate_info) {
                $order_data['affiliate_id'] = $affiliate_info['customer_id'];
                $order_data['commission'] = ($subtotal / 100) * $affiliate_info['commission'];
            } else {
                $order_data['affiliate_id'] = 0;
                $order_data['commission'] = 0;
            }
        } else {
            $order_data['affiliate_id'] = 0;
            $order_data['commission'] = 0;
        }

        $order_data['marketing_id'] = 0;
        $order_data['tracking'] = '';

        $order_data['language_id'] = $this->config->get('config_language_id');
        $order_data['currency_id'] = $this->currency->getId($this->session->data['currency']);
        $order_data['currency_code'] = $this->session->data['currency'];
        $order_data['currency_value'] = $this->currency->getValue($this->session->data['currency']);
        $order_data['ip'] = $this->request->server['REMOTE_ADDR'];

        if (!empty($this->request->server['HTTP_X_FORWARDED_FOR'])) {
            $order_data['forwarded_ip'] = $this->request->server['HTTP_X_FORWARDED_FOR'];
        } elseif (!empty($this->request->server['HTTP_CLIENT_IP'])) {
            $order_data['forwarded_ip'] = $this->request->server['HTTP_CLIENT_IP'];
        } else {
            $order_data['forwarded_ip'] = '';
        }

        $order_data['user_agent'] = $this->request->server['HTTP_USER_AGENT'];
        $order_data['accept_language'] = $this->request->server['HTTP_ACCEPT_LANGUAGE'];

        $order_id = $this->model_pos_pos->addOrder($order_data);
        $this->model_pos_pos->addOrderHistory($order_id, 1);//will change to payment status or redirect to invoice paytabs will do here code

        $this->model_pos_cart->clear();

        $json['success'] = sprintf($this->language->get('text_order_complete'), $order_id);
        $json['order_id'] = $order_id;

        // إدراج الفاتورة في الطابور بعد إتمام الطلب
        $this->queueInvoice($order_id); //maybe add journal entire and send invoice to eta
    } else {
        $json['error'] = $this->language->get('error_order_complete');
    }

    $this->response->addHeader('Content-Type: application/json');
    $this->response->setOutput(json_encode($json));
}
*/
public function completeSale() {
    $this->load->language('pos/pos');
    $this->load->model('pos/pos');
    $this->load->model('pos/cart');
    $this->load->model('pos/transaction');

    $json = array();

    // التحقق من وجود مناوبة نشطة
    if ($this->config->get('pos_require_shift') && !isset($this->session->data['active_shift'])) {
        $json['error'] = $this->language->get('error_no_active_shift');
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
        return;
    }

    if (!isset($this->session->data['pos_customer_id'])) {
        $json['error'] = $this->language->get('error_customer_required');
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
        return;
    }

    if (isset($this->request->post['payment_method']) && isset($this->request->post['shipping_method'])) {
        $payment_method = $this->request->post['payment_method'];
        $shipping_method = $this->request->post['shipping_method'];

        // استدعاء دالة إعداد بيانات الطلب
        $order_data = $this->prepareOrderData($payment_method, $shipping_method);

        $order_id = $this->model_pos_pos->addOrder($order_data);

        if ($order_id) {
            $this->model_pos_pos->addOrderHistory($order_id, 2);
            
            // تنفيذ التحويلات الفعلية للوحدات
            $this->executeUnitConversions();
            
            // تحديث المخزون
            $this->updateInventory($order_id);
            
            // إضافة معاملة في جدول المعاملات
            if (isset($this->session->data['active_shift'])) {
                $transaction_data = array(
                    'shift_id' => $this->session->data['active_shift']['shift_id'],
                    'order_id' => $order_id,
                    'type' => 'sale',
                    'payment_method' => $payment_method,
                    'amount' => $order_data['total'],
                    'reference' => $order_id,
                    'notes' => $this->language->get('text_pos_sale')
                );
                $this->model_pos_transaction->addTransaction($transaction_data);
            }
            
            $json['success'] = sprintf($this->language->get('text_order_complete'), $order_id);
            $json['order_id'] = $order_id;

            $this->model_pos_cart->clear();
        } else {
            $json['error'] = $this->language->get('error_order_complete');
        }
    } else {
        $json['error'] = $this->language->get('error_payment_shipping_required');
    }

    $this->response->addHeader('Content-Type: application/json');
    $this->response->setOutput(json_encode($json));
}

private function executeUnitConversions() {
    if (isset($this->session->data['unit_conversions'])) {
        foreach ($this->session->data['unit_conversions'] as $conversion) {
            $result = $this->model_pos_pos->convertProductUnits(
                $conversion['product_id'],
                $conversion['from_unit_id'],
                $conversion['to_unit_id'],
                $conversion['quantity'],
                $conversion['branch_id']
            );
            if (!$result) {
                // يمكنك إضافة معالجة الأخطاء هنا إذا فشل التحويل
                $this->log->write("Failed to convert units for product ID: " . $conversion['product_id']);
            }
        }
        unset($this->session->data['unit_conversions']);
    }
}

protected function updateInventory($order_id) {
    $this->load->model('pos/cart');
    $this->load->model('catalog/product');
    $this->load->model('pos/inventory');
    $this->load->model('accounts/journal');
    
    // الحصول على تفاصيل الطلب
    $products = $this->model_pos_cart->getProducts();
    $order_info = $this->model_pos_pos->getOrder($order_id);
    
    // معلومات المناوبة
    $shift_id = isset($this->session->data['active_shift']) ? $this->session->data['active_shift']['shift_id'] : 0;
    $branch_id = isset($this->session->data['active_shift']) ? $this->session->data['active_shift']['branch_id'] : $this->config->get('config_branch_id');
    
    // بدء إعداد القيد المحاسبي للمبيعات وتحديث المخزون
    $journal_data = array(
        'reference_id'     => $order_id,
        'reference_type'   => 'sale',
        'description'      => 'بيع منتجات - طلب رقم #' . $order_id,
        'date'             => date('Y-m-d'),
        'entries'          => array()
    );
    
    $total_cost = 0;
    $total_sale = 0;
    
    // معالجة كل منتج في الطلب
    foreach ($products as $product) {
        $product_id = $product['product_id'];
        $quantity = $product['quantity'];
        $unit_id = $product['unit_id'];
        $price = $product['price'];
        $total = $product['total'];
        $total_sale += $total;
        
        // الحصول على معلومات المنتج
        $product_info = $this->model_catalog_product->getProduct($product_id);
        
        // التحقق من وجود المنتج في المخزون
        $inventory_info = $this->model_pos_inventory->getProductInventory($product_id, $branch_id, $unit_id);
        
        if ($inventory_info) {
            $current_quantity = $inventory_info['quantity'];
            $current_cost = $inventory_info['average_cost'];
            $new_quantity = $current_quantity - $quantity;
            
            // حساب تكلفة المنتج المباع
            $item_cost = $current_cost * $quantity;
            $total_cost += $item_cost;
            
            // تحديث المخزون
            $this->model_pos_inventory->updateProductQuantity($product_id, $branch_id, $unit_id, $new_quantity);
            
            // إضافة حركة المنتج
            $movement_data = array(
                'product_id'        => $product_id,
                'type'              => 'sale',
                'movement_reference_type' => 'order',
                'movement_reference_id' => $order_id,
                'date_added'        => date('Y-m-d H:i:s'),
                'quantity'          => -$quantity, // سالب لأنها عملية سحب من المخزون
                'unit_cost'         => $current_cost,
                'unit_id'           => $unit_id,
                'branch_id'         => $branch_id,
                'reference'         => 'ORDER #' . $order_id,
                'old_average_cost'  => $current_cost,
                'new_average_cost'  => $current_cost, // لن تتغير التكلفة عند البيع
                'user_id'           => $this->user->getId(),
                'effect_on_cost'    => 'no_change'
            );
            $movement_id = $this->model_pos_inventory->addProductMovement($movement_data);
            
            // إضافة سجل لتقييم المخزون
            $valuation_data = array(
                'product_id'        => $product_id,
                'unit_id'           => $unit_id,
                'branch_id'         => $branch_id,
                'valuation_date'    => date('Y-m-d'),
                'average_cost'      => $current_cost,
                'quantity'          => $new_quantity,
                'total_value'       => $current_cost * $new_quantity,
                'date_added'        => date('Y-m-d H:i:s'),
                'transaction_reference_id' => $order_id,
                'transaction_type'  => 'sale',
                'previous_quantity' => $current_quantity,
                'previous_cost'     => $current_cost,
                'movement_quantity' => -$quantity,
                'movement_cost'     => $current_cost
            );
            $this->model_pos_inventory->addInventoryValuation($valuation_data);
        }
    }
    
    // إنشاء القيود المحاسبية
    
    // 1. تسجيل إيراد المبيعات - دائن
    $journal_data['entries'][] = array(
        'account_code' => $this->config->get('config_sales_revenue_account'),
        'is_debit'     => 0,
        'amount'       => $total_sale
    );
    
    // 2. المدين - حساب النقدية (أو حساب المدينين)
    $payment_method = $order_info['payment_method'];
    $account_code = $this->getPaymentAccountCode($payment_method);
    
    $journal_data['entries'][] = array(
        'account_code' => $account_code,
        'is_debit'     => 1,
        'amount'       => $total_sale
    );
    
    // 3. تسجيل تكلفة المبيعات - مدين
    $journal_data['entries'][] = array(
        'account_code' => $this->config->get('config_cost_of_goods_sold_account'),
        'is_debit'     => 1,
        'amount'       => $total_cost
    );
    
    // 4. تخفيض المخزون - دائن
    $journal_data['entries'][] = array(
        'account_code' => $this->config->get('config_inventory_account'),
        'is_debit'     => 0,
        'amount'       => $total_cost
    );
    
    // إنشاء القيد المحاسبي
    $journal_id = $this->model_accounts_journal->addJournal($journal_data);
    
    // تحديث الطلب برقم القيد
    $this->db->query("UPDATE `" . DB_PREFIX . "order` SET journal_id = '" . (int)$journal_id . "' WHERE order_id = '" . (int)$order_id . "'");
    
    // تسجيل COGS (تكلفة البضاعة المباعة) لهذا الطلب
    $this->db->query("INSERT INTO " . DB_PREFIX . "order_cogs SET 
        order_id = '" . (int)$order_id . "', 
        total_cogs = '" . (float)$total_cost . "',
        date_added = NOW()");
    
    // إذا كان المنتج مرتبط بمناوبة، تحديث معلومات المناوبة
    if ($shift_id) {
        $this->db->query("UPDATE " . DB_PREFIX . "pos_shift SET 
            last_order_id = '" . (int)$order_id . "',
            total_sales = total_sales + " . (float)$total_sale . ",
            total_cogs = total_cogs + " . (float)$total_cost . "
            WHERE shift_id = '" . (int)$shift_id . "'");
    }
    
    return true;
}

/**
 * الحصول على رمز الحساب المناسب لطريقة الدفع
 */
private function getPaymentAccountCode($payment_method) {
    // التعرف على طريقة الدفع وإرجاع كود الحساب المناسب
    switch(strtolower($payment_method)) {
        case 'cash':
            return $this->config->get('config_cash_account'); // حساب النقدية
        case 'credit_card':
        case 'debit_card':
        case 'visa':
        case 'mastercard':
            return $this->config->get('config_card_account'); // حساب البطاقات البنكية
        case 'bank_transfer':
            return $this->config->get('config_bank_account'); // حساب البنك
        case 'cod': // الدفع عند الاستلام
            return $this->config->get('config_receivables_account'); // حساب المدينين
        default:
            return $this->config->get('config_other_payment_account'); // حساب آخر
    }
}

private function prepareOrderData($payment_method, $shipping_method) {
    $this->load->model('customer/customer');
    $customer_info = $this->model_customer_customer->getCustomer($this->session->data['pos_customer_id']);
    $order_data = array();
    
    // إضافة معلومات الكاشير
    $order_data['order_posuser_id'] = $this->user->getId();
    $order_data['order_posuser_name'] = $this->user->getUserName();
    
    // حفظ معرف المناوبة في البيانات
    if (isset($this->session->data['active_shift'])) {
        $order_data['shift_id'] = $this->session->data['active_shift']['shift_id'];
    } else {
        $order_data['shift_id'] = 0;
    }
    
    // بيانات العميل الأساسية
    $order_data['customer_id'] = $customer_info['customer_id'];
    $order_data['customer_group_id'] = $customer_info['customer_group_id'];
    $order_data['firstname'] = $customer_info['firstname'];
    $order_data['lastname'] = $customer_info['lastname'] ?? '';
    $order_data['email'] = $customer_info['email'];
    $order_data['telephone'] = $customer_info['telephone'];
    
    // الحصول على عناوين العميل
    $addresses = $this->model_customer_customer->getAddresses($customer_info['customer_id']);
    
    // استخدام العنوان الأول إذا كان متاحًا، وإلا استخدام بيانات افتراضية
    $address_info = !empty($addresses) ? reset($addresses) : array(
        'firstname' => $customer_info['firstname'],
        'lastname'  => $customer_info['lastname'] ?? '',
        'company'   => '',
        'address_1' => '',
        'address_2' => '',
        'city'      => '',
        'postcode'  => '',
        'zone'      => '',
        'zone_id'   => 0,
        'country'   => '',
        'country_id' => 0
    );

    // تعيين بيانات الدفع والشحن
    foreach (['payment', 'shipping'] as $type) {
        foreach ($address_info as $key => $value) {
            $order_data["{$type}_{$key}"] = $value;
        }
        $order_data["{$type}_address_format"] = '';
    }

    $order_data['payment_method'] = $payment_method;
    $order_data['payment_code'] = $payment_method;
    $order_data['shipping_method'] = $shipping_method;
    $order_data['shipping_code'] = $shipping_method;

    // المنتجات
    $order_data['products'] = array();
    foreach ($this->model_pos_cart->getProducts() as $product) {
        $order_data['products'][] = array(
            'product_id' => $product['product_id'],
            'name'       => $product['name'],
            'model'      => $product['model'],
            'option'     => $product['option'],
            'download'   => $product['download'] ?? [],
            'quantity'   => $product['quantity'],
            'subtract'   => $product['subtract'],
            'price'      => $product['price'],
            'total'      => $product['total'],
            'tax'        => $this->tax->getTax($product['price'], $product['tax_class_id']),
            'reward'     => $product['reward'] ?? 0,
            'unit_id'    => $product['unit_id'] // إضافة معرف الوحدة
        );
    }

    // الإجماليات
    $totals = $this->model_pos_pos->getTotals();
    $order_data['totals'] = $totals['totals'];
    $order_data['total'] = $totals['total'];

    $order_data['comment'] = '';
    $order_data['affiliate_id'] = 0;
    $order_data['commission'] = 0;
    $order_data['marketing_id'] = 0;
    $order_data['tracking'] = '';
    $order_data['language_id'] = $this->config->get('config_language_id');
    $order_data['currency_id'] = $this->currency->getId($this->config->get('config_currency'));
    $order_data['currency_code'] = $this->config->get('config_currency');
    $order_data['currency_value'] = $this->currency->getValue($this->config->get('config_currency'));
    $order_data['ip'] = $this->request->server['REMOTE_ADDR'];

    if (isset($this->request->post['rin_customer'])) {
        $order_data['rin_customer'] = $this->request->post['rin_customer'];
    } else {
        $order_data['rin_customer'] = '';
    }

    return $order_data;
}

private function queueInvoice($order_id) {
    $this->load->model('queue/queue');
    $this->load->model('pos/pos');

    $order_info = $this->model_pos_pos->getOrder($order_id);
    
    if ($order_info) {
        $invoice_data = $this->prepareInvoiceData($order_id, $order_info);
        $job = [
            'task' => 'send_invoice',
            'data' => [
                'invoice_data' => $invoice_data
            ]
        ];
        $this->model_queue_queue->addJob(json_encode($job));
    }
}

private function prepareInvoiceData($order_id, $order_info) {
    $issuer_id = $this->config->get('config_eta_taxpayer_id');
    $issuer_name = $this->config->get('config_name');
    $issuer_branch_code = '0';
    $totalProductsAmount = 0.0;
    $totalOrderAmount = 0.0;
    $totalDiscountAmount = 0.0;
    $totalTaxableFees = 0.0;
    $totalItemsDiscountAmount = 0.0;
    $taxTotalsAmount = 0.0;

    // Get order products
    $order_products = $this->db->query("SELECT * FROM " . DB_PREFIX . "order_product WHERE order_id = " . (int)$order_id)->rows;

    // Get order totals
    $order_totals = $this->db->query("SELECT * FROM " . DB_PREFIX . "order_total WHERE order_id = " . (int)$order_id)->rows;

    foreach ($order_totals as $total) {
        if ($total['code'] == 'sub_total') {
            $totalProductsAmount = round((float)$total['value'], 5);
        } elseif ($total['code'] == 'tax') {
            $taxTotalsAmount += round((float)$total['value'], 5);
        } elseif ($total['code'] == 'total') {
            $totalOrderAmount += round((float)$total['value'], 5);            
        } elseif ($total['value'] < 0) { // For discounts and coupons
            $totalDiscountAmount += abs(round((float)$total['value'], 5));
        }
    }

    $invoice_lines = [];
    foreach ($order_products as $product) {
        $egs_code_query = $this->db->query("SELECT egs_code FROM " . DB_PREFIX . "product_egs WHERE product_id = " . (int)$product['product_id']);
        $egs_code = $egs_code_query->num_rows ? $egs_code_query->row['egs_code'] : '';

        // Calculate item values
        $item_price = round((float)$product['price'], 5);
        $item_quantity = round((float)$product['quantity'], 5);
        $sub_total = round((float)($product['price'] * $product['quantity']), 5);
        $item_tax = round((float)($sub_total * 0.14), 5); // Assuming 14% tax rate
        $item_total = round(($sub_total + $item_tax), 5);
        $item_discount = 0.0;

        $invoice_lines[] = [
            'description' => $product['name'],
            'itemType' => 'EGS',
            'itemCode' => $egs_code,
            'unitType' => 'C62', // Use the appropriate unit type
            'quantity' => $item_quantity,
            'unitValue' => [
                'currencySold' => 'EGP',
                'amountEGP' => $item_price
            ],
            'salesTotal' => $sub_total, // السعر * الكمية
            'total' => $item_total, // إجمالي سعر المنتج + الضرائب
            'netTotal' => $sub_total, // إجمالي سعر المنتج بعد الخصم
            'valueDifference' => 0.00, // Adjust as needed
            'totalTaxableFees' => 0.00000,
            'itemsDiscount' => $item_discount,
            'taxableItems' => [
                [
                    'taxType' => 'T1',
                    'amount' => $item_tax,
                    'subType' => 'V009',
                    'rate' => 14.00 // 14% tax rate
                ]
            ],
            'internalCode' => (string)(int)$product['product_id']
        ];

        // Summing up taxable fees for the invoice
        $totalTaxableFees += $item_tax;
    }

    // Calculate the net amount and total amount
    $netAmount = round($totalProductsAmount - $totalDiscountAmount, 5);
    $totalAmount = round(($netAmount + $totalTaxableFees), 5);

    $invoice_data = [
        'documents' => [
            [
                'issuer' => [
                    'type' => 'B',
                    'id' => $issuer_id,
                    'name' => $issuer_name,
                    'address' => [
                        'branchID' => $issuer_branch_code,
                        'country' => 'EG',
                        'governate' => $this->config->get('config_governate'),
                        'regionCity' => $this->config->get('config_region_city'),
                        'street' => $this->config->get('config_street'),
                        'buildingNumber' => $this->config->get('config_building_number'),
                        'postalCode' => '',
                        'floor' => '',
                        'room' => '',
                        'landmark' => '',
                        'additionalInformation' => ''
                    ]
                ],
                'receiver' => [
                    'type' => ($order_info['customer_group_id'] == '1') ? 'P' : 'B',
                    'id' => $order_info['rin_customer'] ?? '',
                    'name' => $order_info['payment_firstname'] . ' ' . $order_info['payment_lastname'],
                    'address' => [
                        'country' => 'EG',
                        'governate' => $order_info['payment_zone'],
                        'regionCity' => $order_info['payment_city'],
                        'street' => $order_info['payment_address_1'],
                        'buildingNumber' => $order_info['payment_address_2'] ?? '',
                        'postalCode' => '',
                        'floor' => '',
                        'room' => '',
                        'landmark' => '',
                        'additionalInformation' => ''
                    ]
                ],
                'documentType' => 'I',
                'documentTypeVersion' => '0.9',
                'dateTimeIssued' => gmdate('Y-m-d\TH:i:s\Z'),
                'taxpayerActivityCode' => $this->config->get('config_eta_activity_code'),
                'internalID' => (string)(int)$order_info['order_id'],
                'purchaseOrderReference' => '',
                'purchaseOrderDescription' => '',
                'salesOrderReference' => '',
                'salesOrderDescription' => '',
                'proformaInvoiceNumber' => '',
                'invoiceLines' => $invoice_lines,
                'totalSalesAmount' => round($totalProductsAmount, 5), // Ensure numeric value
                'totalDiscountAmount' => 0.0000, // Ensure numeric value
                'netAmount' => round($totalProductsAmount, 5), // Ensure numeric value
                'taxTotals' => [
                    [
                        'taxType' => 'T1',
                        'amount' => round($totalTaxableFees, 5), // Ensure numeric value
                    ]
                ],
                'totalAmount' => round($totalAmount, 5), // Ensure numeric value
                'extraDiscountAmount' => round($totalDiscountAmount, 5),  // order coupon discount
                'totalItemsDiscountAmount' => 0.00000, // item not have discount will test it
                'signatures' => [] // Add this empty array to avoid validation error
            ]
        ]
    ];

    // Log the invoice data for debugging

    return $invoice_data;
}


public function remove($key) {
    if (isset($this->session->data['cart'][$key])) {
        $this->load->model('pos/pos');
        $product_id = $this->session->data['cart'][$key]['product_id'];
        $quantity = $this->session->data['cart'][$key]['quantity'];
        $unit_id = $this->session->data['cart'][$key]['unit_id'];
        
        $base_unit_id = $this->model_pos_pos->getProductBaseUnit($product_id);
        $base_quantity = $this->model_pos_pos->convertUnits($product_id, $unit_id, $base_unit_id, $quantity);
        
        // Return quantity to inventory (in base unit)
        $this->model_pos_pos->updateProductQuantity($product_id, $base_unit_id, $base_quantity);
        $this->model_pos_pos->addInventoryHistory($product_id, $base_unit_id, $base_quantity, 'add');
        
        unset($this->session->data['cart'][$key]);
    }
    
    $this->data = array();
}



// تعديل دالة voidSale للتكامل مع نظام المعاملات
public function voidSale() {
    $json = array();
    $this->load->model('pos/cart');
    $this->load->model('pos/transaction');
    
    // إضافة سجل معاملة من نوع إلغاء إذا كان هناك مناوبة نشطة
    if (isset($this->session->data['active_shift'])) {
        $cart_products = $this->model_pos_cart->getProducts();
        $total = 0;
        foreach ($cart_products as $product) {
            $total += $product['total'];
        }
        
        if ($total > 0) {
            $transaction_data = array(
                'shift_id' => $this->session->data['active_shift']['shift_id'],
                'order_id' => null,
                'type' => 'void',
                'payment_method' => 'N/A',
                'amount' => $total,
                'reference' => 'POS Void',
                'notes' => $this->language->get('text_pos_void')
            );
            $this->model_pos_transaction->addTransaction($transaction_data);
        }
    }
    
    // إلغاء السلة الحالية
    $this->model_pos_cart->clear();

    $json['success'] = $this->language->get('text_void_success');

    $this->response->addHeader('Content-Type: application/json');
    $this->response->setOutput(json_encode($json));
}

public function suspendSale() {
    $this->load->language('pos/pos');
    $json = array();

    $this->load->model('pos/cart');
    $this->load->model('customer/customer');

    $cart_products = $this->model_pos_cart->getProducts();

    $customer_id = isset($this->session->data['pos_customer_id']) ? $this->session->data['pos_customer_id'] : 0;
    $coupon = isset($this->session->data['coupon']) ? $this->session->data['coupon'] : null;
    $payment_method = isset($this->session->data['payment_method']['title']) ? $this->session->data['payment_method']['title'] : null;
    $shipping_method = isset($this->session->data['shipping_method']['title']) ? $this->session->data['shipping_method']['title'] : null;
    
    // إضافة معرف المناوبة إلى السلة المعلقة
    $shift_id = isset($this->session->data['active_shift']) ? $this->session->data['active_shift']['shift_id'] : 0;

    if (!isset($this->session->data['suspended_carts'])) {
        $this->session->data['suspended_carts'] = array();
    }

    $suspend_id = uniqid();
    $this->session->data['suspended_carts'][$suspend_id] = array(
        'products' => $cart_products,
        'customer_id' => $customer_id,
        'coupon' => $coupon,
        'payment_method' => $payment_method,
        'shipping_method' => $shipping_method,
        'shift_id' => $shift_id,
        'date_added' => date('Y-m-d H:i:s')
    );

    $this->model_pos_cart->clear();

    $json['success'] = $this->language->get('text_suspend_success');

    $this->response->addHeader('Content-Type: application/json');
    $this->response->setOutput(json_encode($json));
}



    public function printReceipt() {
        $json = array();

        // التحقق من وجود معرف الطلب
        if (isset($this->request->get['order_id'])) {
            $order_id = $this->request->get['order_id'];

            // الحصول على بيانات الطلب
            $this->load->model('pos/pos');
            $order_info = $this->model_pos_pos->getOrder($order_id);

            if ($order_info) {
                // إنشاء إيصال الطباعة
                $json['receipt'] = $this->load->view('pos/receipt', array('order_info' => $order_info));

                $json['success'] = $this->language->get('text_print_success');
            } else {
                $json['error'] = $this->language->get('error_order_not_found');
            }
        } else {
            $json['error'] = $this->language->get('error_order_id');
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function searchCustomers() {
        $json = array();

        if (isset($this->request->get['query'])) {
            $query = $this->request->get['query'];
        } else {
            $query = '';
        }

        $this->load->model('pos/pos');
        $results = $this->model_pos_pos->searchCustomers($query);

        if ($results) {
            foreach ($results as $result) {
                $json[] = array(
                    'customer_id' => $result['customer_id'],
                    'name'        => $result['name'],
                    'email'       => $result['email']
                );
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

public function selectCustomer() {
    $json = array();
    $this->load->model('pos/pos');
    $this->load->model('pos/cart');
    if (isset($this->request->post['customer_id'])) {
        $customer_id = (int)$this->request->post['customer_id'];
        $this->session->data['pos_customer_id'] = $customer_id;

        $this->load->model('customer/customer');
        $customer_info = $this->model_customer_customer->getCustomer($customer_id);
        $totals = $this->model_pos_pos->getTotals();

        if ($customer_info) {
            $json['success'] = $this->language->get('text_customer_selected');
            $json['customer_name'] = $customer_info['firstname'] . ' ' . $customer_info['lastname'];
            $json['customer_group_id'] = $customer_info['customer_group_id'];
			$json['products'] = $this->model_pos_cart->getProducts();
			$totals = $this->model_pos_pos->getTotals(); 
			$json['totals'] = $totals['totals'];
			$json['total'] = $totals['total'];
		
        } else {
            $json['error'] = $this->language->get('error_customer_selection');
        }
    } else {
        $json['error'] = $this->language->get('error_customer_selection');
    }

    $this->response->addHeader('Content-Type: application/json');
    $this->response->setOutput(json_encode($json));
}

    protected function generateRandomPassword($length = 8) {
        $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $password = '';
        for ($i = 0; $i < $length; $i++) {
            $password .= $characters[rand(0, strlen($characters) - 1)];
        }
        return $password;
    }
	
public function addNewCustomer() {
    $this->load->language('pos/pos');
    $this->load->model('customer/customer');

    if ($this->request->server['REQUEST_METHOD'] == 'POST') {
        $json = array();

        // التحقق من صحة البيانات

		if (!$this->validateForm()) {
			$json['error'] = $this->language->get('error_invalid_data');
			$this->response->addHeader('Content-Type: application/json');
			$this->response->setOutput(json_encode($json));
			return;
		}

        // تحقق من وجود العميل بالإيميل أو الهاتف
        $customer_info = $this->model_customer_customer->getCustomerByEmailOrPhone($this->request->post['email'], $this->request->post['telephone']);

        if ($customer_info) {
            $json['error'] = $this->language->get('error_exists');
        }

        if (!isset($json['error'])) {
            // إعداد بيانات العميل
            $password = $this->generateRandomPassword();
            $customer_data = array(
                'firstname' => $this->request->post['name'],
                'lastname' => '',
                'email' => $this->request->post['email'],
                'telephone' => $this->request->post['phone'],
                'fax' => '',
                'custom_field' => array(),
                'password' => $password,
                'customer_group_id' => $this->request->post['customer_group_id'],
                'status' => 1,
                'address' => array(
                    array(
                        'firstname' => $this->request->post['name'],
                        'lastname' => '',
                        'company' => '',
                        'address_1' => $this->request->post['address_1'],
                        'address_2' => $this->request->post['address_2'],
                        'city' => $this->request->post['city'],
                        'postcode' => '',
                        'country_id' => $this->config->get('config_country_id'), // افتراضياً يمكن استخدام معرف البلد من الإعدادات
                        'zone_id' => $this->request->post['zone_id'],
                        'custom_field' => array()
                    )
                )
            );
			
            // إضافة العميل
            $customer_id = $this->model_customer_customer->addCustomer($customer_data);

            // إضافة حساب إلى شجرة الحسابات
            $account_code = $this->generateAccountCode();
            $account_data = array(
                'account_code' => $account_code,
                'parent_id' => 1231, // افتراض أن حساب العملاء يقع تحت الكود 1231
                'status' => 1,
                'account_description' => array(
                    1 => array('name' => $this->request->post['name'])
                )
            );
            $this->addAccount($account_data);

            $this->db->query("UPDATE " . DB_PREFIX . "customer SET account_code = '" . $this->db->escape($account_code) . "' WHERE customer_id = '" . (int)$customer_id . "'");

            $json['success'] = $this->language->get('text_success_add_customer');
            $json['customer_id'] = $customer_id;
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
}

    private function addAccount($data) {
        $this->db->query("INSERT INTO " . DB_PREFIX . "accounts SET account_code = '" . $this->db->escape($data['account_code']) . "', parent_id = '" . (int)$data['parent_id'] . "', status = '" . (int)$data['status'] . "', date_modified = NOW(), date_added = NOW()");

        $account_id = $this->db->getLastId();

        foreach ($data['account_description'] as $language_id => $value) {
            $this->db->query("INSERT INTO " . DB_PREFIX . "account_description SET account_id = '" . (int)$account_id . "', language_id = '" . (int)$language_id . "', name = '" . $this->db->escape($value['name']) . "'");
        }

        $this->cache->delete('account');

        return $account_id;
    }
	
private function generateAccountCode() {
    $base_code = 1231; // تعديل الكود الأساسي حسب شجرة الحسابات الخاصة بك
    $last_account_code = $this->db->query("SELECT MAX(account_code) AS max_code FROM " . DB_PREFIX . "accounts WHERE account_code LIKE '1231%'")->row['max_code'];
    $new_code = $last_account_code ? intval($last_account_code) + 1 : $base_code . '0000001';
    return $new_code;
}

protected function validateForm() {
    if (!$this->user->hasPermission('modify', 'pos/pos')) {
        $this->error['warning'] = $this->language->get('error_permission');
    }

    if ((utf8_strlen($this->request->post['name']) < 1) || (utf8_strlen($this->request->post['name']) > 32)) {
        $this->error['name'] = $this->language->get('error_name');
    }

    if ((utf8_strlen($this->request->post['phone']) < 1) || (utf8_strlen($this->request->post['phone']) > 32)) {
        $this->error['phone'] = $this->language->get('error_phone');
    }

    if ((utf8_strlen($this->request->post['email']) > 96) || !filter_var($this->request->post['email'], FILTER_VALIDATE_EMAIL)) {
        $this->error['email'] = $this->language->get('error_email');
    }

    if ((utf8_strlen($this->request->post['address_1']) < 3) || (utf8_strlen($this->request->post['address_1']) > 128)) {
        $this->error['address_1'] = $this->language->get('error_address_1');
    }

    if ((utf8_strlen($this->request->post['city']) < 2) || (utf8_strlen($this->request->post['city']) > 128)) {
        $this->error['city'] = $this->language->get('error_city');
    }

    if ($this->request->post['zone_id'] == '') {
        $this->error['zone'] = $this->language->get('error_zone');
    }

    if ($this->request->post['customer_group_id'] == '') {
        $this->error['customer_group'] = $this->language->get('error_customer_group');
    }

    return !$this->error;
}


public function getProductInventory() {
    $json = array();

    if (isset($this->request->get['product_id'])) {
        $product_id = (int)$this->request->get['product_id'];

        $this->load->model('pos/pos');
        $inventory = $this->model_pos_pos->getProductInventoryx($product_id);

        if ($inventory) {
            $json['success'] = true;
            $json['inventory'] = array();

            $total_inventory = array();
            foreach ($inventory as $item) {
                if (!isset($total_inventory[$item['unit_id']])) {
                    $total_inventory[$item['unit_id']] = array(
                        'unit_id' => $item['unit_id'],
                        'unit_name' => $item['unit_name'],
                        'quantity' => 0,
                        'quantity_available' => 0
                    );
                }
                $total_inventory[$item['unit_id']]['quantity'] += $item['quantity'];
                $total_inventory[$item['unit_id']]['quantity_available'] += $item['quantity_available'];
            }

            $json['inventory'] = array_values($total_inventory);
        } else {
            $json['error'] = $this->language->get('error_no_inventory');
        }
    } else {
        $json['error'] = $this->language->get('error_product_id');
    }

    $this->response->addHeader('Content-Type: application/json');
    $this->response->setOutput(json_encode($json));
}

public function addToCart() {
    $this->load->language('pos/pos');
    $json = array();

    if (!isset($this->request->post['product_id']) || !isset($this->request->post['quantity']) || !isset($this->request->post['unit_id'])) {
        $json['error'] = $this->language->get('error_required');
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
        return;
    }

    $product_id = (int)$this->request->post['product_id'];
    $quantity = (float)$this->request->post['quantity'];
    $unit_id = (int)$this->request->post['unit_id'];
    $option = isset($this->request->post['option']) ? $this->request->post['option'] : array();
    $pricing_type = isset($this->request->post['pricing_type']) ? $this->request->post['pricing_type'] : 'retail';

    $is_admin = $this->user->getGroupId() == 1;
    $branch_id = $is_admin ? 0 : $this->user->getBranchId();

    $this->load->model('pos/pos');
    $this->load->model('pos/cart');

    // نتحقق من المخزون ونقوم بالتحويل المؤقت للوحدات إذا لزم الأمر
    $inventory_data = $this->model_pos_pos->checkAndPrepareInventory($product_id, $quantity, $unit_id, $branch_id);

    if ($inventory_data['success']) {
        $result = $this->model_pos_cart->add($product_id, $inventory_data['quantity'], $option, $inventory_data['unit_id'], $pricing_type, $branch_id);

        if ($result) {
            $json['success'] = $this->language->get('text_success');
            $json['products'] = $this->model_pos_cart->getProducts();
            $totals = $this->model_pos_pos->getTotals(); 
            $json['totals'] = $totals['totals'];
            $json['total'] = $totals['total'];

            if ($inventory_data['converted']) {
                $json['note'] = sprintf($this->language->get('text_conversion_prepared'), $inventory_data['from_unit_name'], $inventory_data['to_unit_name']);
            }
        } else {
            $json['error'] = $this->language->get('error_add_to_cart');
        }
    } else {
        $json['error'] = $inventory_data['message'];
    }

    $this->response->addHeader('Content-Type: application/json');
    $this->response->setOutput(json_encode($json));
}

public function getPOpts() {
    $json = array();

    if (isset($this->request->get['product_id'])) {
        $product_id = (int)$this->request->get['product_id'];

        $this->load->model('pos/pos'); // تحميل النموذج الصحيح
        $options = $this->model_pos_pos->getPOpts($product_id); // استخدام الدالة من النموذج

        if ($options) {
            $json['success'] = true;
            $json['options'] = $options;
        } else {
            $json['error'] = $this->language->get('error_product_options');
        }
    } else {
        $json['error'] = $this->language->get('error_product_id');
    }

    $this->response->addHeader('Content-Type: application/json');
    $this->response->setOutput(json_encode($json));
}


 
     public function getProductOptionsAndPricing() {
        $this->load->model('pos/pos');
        
        if (isset($this->request->get['product_id'])) {
            $product_id = (int)$this->request->get['product_id'];
        } else {
            $product_id = 0;
        }

        $json = array();

        $json['options'] = $this->model_pos_pos->getProductOptions($product_id);
        $json['pricing'] = $this->model_pos_pos->getProductPricing($product_id);

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
	
	
}
