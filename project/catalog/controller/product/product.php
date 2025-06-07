<?php
class ControllerProductProduct extends Controller {
    private $error = array();
public function index() {
    $this->load->language('product/product');
    $this->load->model('catalog/product');
    $this->load->model('tool/image');

    // الحصول على معرف المنتج من المعاملات GET
    if (isset($this->request->get['product_id'])) {
        $product_id = (int)$this->request->get['product_id'];
    } else {
        $product_id = 0;
    }
    // الحصول على معلومات المنتج باستخدام النموذج
    $product_info = $this->model_catalog_product->getProduct($product_id);

    if ($product_info) {
        error_log(json_encode($product_info));
        // تحديث عدد المشاهدات
        $this->model_catalog_product->updateViewed($product_id);

        // إعداد بيانات الوثيقة
        $this->document->setTitle($product_info['meta_title']);
        $this->document->setDescription($product_info['meta_description']);
        $this->document->setKeywords($product_info['meta_keyword']);
		$this->document->addLink($this->url->link('product/product', 'product_id=' . $this->request->get['product_id']), 'canonical');

		$data['heading_title'] = $product_info['name'];
		$data['text_minimum'] = sprintf($this->language->get('text_minimum'), $product_info['minimum']);
		$data['text_login'] = sprintf($this->language->get('text_login'), $this->url->link('account/login', '', true), $this->url->link('account/register', '', true));
$data['description'] = isset($product_info['description']) && $product_info['description'] !== null 
    ? html_entity_decode($product_info['description'], ENT_QUOTES, 'UTF-8') 
    : '';
        $data['breadcrumbs'] = array();
        $data['product_id'] = $product_id;

        // خانة البداية
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/home')
        );


        // إعداد الوحدات المتاحة للمنتج
        $units = $this->model_catalog_product->getProductUnits($product_id);
        if ($units) {
            $data['product_units'] = $units;
            $default_unit = $this->model_catalog_product->getDefaultUnit($units);
            $data['default_unit'] = $default_unit;
            $default_unit_id = is_array($default_unit) && isset($default_unit['unit_id']) ? $default_unit['unit_id'] : 37;
        } else {
            $data['product_units'] = false;
            $data['default_unit'] = false;
            $default_unit_id = 37; // قيمة افتراضية إذا لم تكن هناك وحدات
        }

        // تحديد الكمية المتاحة
        if ($default_unit_id) {
            $available_quantity = $this->model_catalog_product->getAvailableQuantityForOnline($product_id, $default_unit_id);
        } else {
            $available_quantity = $this->model_catalog_product->getAvailableQuantityForOnline($product_id, 0); // استخدام وحدة افتراضية
        }

        // تحديد الكمية الأولية
        $minimum_quantity = max(1, (int)$product_info['minimum']);
        $initial_quantity = $minimum_quantity;


        // حساب بيانات السعر الأولي باستخدام النموذج
        try {
            $price_data = $this->model_catalog_product->getUnitPriceData($product_id, $default_unit_id, $initial_quantity, array());
            if ($price_data['success']) {
                $data['price_data'] = $price_data['price_data'];
                $data['quantity_data'] = $price_data['quantity_data'];
                $data['discount_data'] = $price_data['discount_data'] ? array($price_data['discount_data']) : array();
                $data['product_quantity_discounts'] = !empty($price_data['product_quantity_discounts']) ? $price_data['product_quantity_discounts'] : array();
                $data['product_bundles'] = !empty($price_data['product_bundles']) ? $price_data['product_bundles'] : array();
                $data['next_discount'] = $price_data['next_discount'];
				

            } else {
                throw new Exception($this->language->get('error_price_calculation'));
            }
        } catch (Exception $e) {
            $data['error'] = $e->getMessage();
            $data['price_data'] = array();
            $data['quantity_data'] = array(
                'minimum' => $minimum_quantity,
                'maximum' => $available_quantity,
                'current' => $initial_quantity,
                'available' => $available_quantity
            );
            $data['product_quantity_discounts'] = array();
            $data['product_bundles'] = array();	
            $data['next_discount'] = null;

        }

        // إعداد الصور الرئيسية والفرعية
        if ($product_info['image']) {
            $data['popup'] = $this->model_tool_image->resize($product_info['image'], 800, 800);
            $data['thumb'] = $this->model_tool_image->resize($product_info['image'], 200, 200);
        } else {
            $data['popup'] = $this->model_tool_image->resize('placeholder.png', 800, 800);
            $data['thumb'] = $this->model_tool_image->resize('placeholder.png', 200, 200);
        }

        // إعداد صور المنتج الإضافية
        $data['images'] = array();
        $results = $this->model_catalog_product->getProductImages($product_id);
        foreach ($results as $result) {
            $data['images'][] = array(
                'thumb' => $this->model_tool_image->resize($result['image'], 200, 200)
            );
        }

        // إعداد الخيارات
        $data['options'] = $this->model_catalog_product->getProductOptionsByUnit($product_id, $default_unit_id);

        // إعداد الباقات
        $data['product_bundles'] = $this->model_catalog_product->getProductBundles($product_id);

        // إعداد التوصيات
        $recommendations = $this->model_catalog_product->getProductRecommendations($product_id);
        $data['product_recommendations'] = $recommendations;

        // إعداد الأوصاف والخصائص

        // إعداد النصوص والأزرار
        $data['button_cart'] = $this->language->get('button_cart');
        $data['button_wishlist'] = $this->language->get('button_wishlist');
        $data['button_compare'] = $this->language->get('button_compare');
        $data['button_continue'] = $this->language->get('button_continue');

        // إعداد نصوص إضافية
        $data['text_invalid_quantity'] = $this->language->get('text_invalid_quantity');
        $data['text_loading'] = $this->language->get('text_loading');
        $data['text_error'] = $this->language->get('text_error');
        $data['text_you_save'] = $this->language->get('text_you_save');
        $data['text_minimum'] = $this->language->get('text_minimum');
        $data['text_buy_together'] = $this->language->get('text_buy_together');
        $data['text_discount'] = $this->language->get('text_discount');
        $data['text_current_quantity'] = $this->language->get('text_current_quantity');
        $data['text_next_discount_at'] = $this->language->get('text_next_discount_at');
        $data['text_quantity_discounts'] = $this->language->get('text_quantity_discounts');
        $data['text_upsell'] = $this->language->get('text_upsell');
        $data['text_cross_sell'] = $this->language->get('text_cross_sell');
        $data['text_select_options'] = $this->language->get('text_select_options');

        // تحميل القوالب الجانبية
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['column_right'] = $this->load->controller('common/column_right');
        $data['content_top'] = $this->load->controller('common/content_top');
        $data['content_bottom'] = $this->load->controller('common/content_bottom');
        $data['footer'] = $this->load->controller('common/footer');
        $data['header'] = $this->load->controller('common/header');

        // تحميل العرض النهائي للمنتج
        $this->response->setOutput($this->load->view('product/product', $data));
        } else {
            // معالجة حالة عدم وجود المنتج
            $data['breadcrumbs'][] = array(
                'text' => $this->language->get('text_error'),
                'href' => $this->url->link('product/product', 'product_id=' . $product_id)
            );

            $this->document->setTitle($this->language->get('text_error'));
            $data['continue'] = $this->url->link('common/home');

            $this->response->addHeader($this->request->server['SERVER_PROTOCOL'] . ' 404 Not Found');

            $data['column_left'] = $this->load->controller('common/column_left');
            $data['column_right'] = $this->load->controller('common/column_right');
            $data['content_top'] = $this->load->controller('common/content_top');
            $data['content_bottom'] = $this->load->controller('common/content_bottom');
            $data['footer'] = $this->load->controller('common/footer');
            $data['header'] = $this->load->controller('common/header');

            $this->response->setOutput($this->load->view('error/not_found', $data));
        }
}

public function quickview() {
    $this->load->language('product/product');
    $this->load->model('catalog/product');
    $this->load->model('tool/image');

    $data = [];

    if (isset($this->request->get['product_id'])) {
        $product_id = (int)$this->request->get['product_id'];
    } else {
        $product_id = 0;
    }

    $product_info = $this->model_catalog_product->getProduct($product_id);

    if ($product_info) {
        // Basic product information
        $data['heading_title'] = $product_info['name'];
        $data['product_id'] = $product_id;
        $data['description'] = isset($product_info['description']) && $product_info['description'] !== null 
            ? html_entity_decode($product_info['description'], ENT_QUOTES, 'UTF-8') 
            : '';
        
        // Breadcrumbs
        $data['breadcrumbs'] = array();
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/home')
        );
        
        // Images
        if ($product_info['image']) {
            $data['popup'] = $this->model_tool_image->resize($product_info['image'], 800, 800);
            $data['thumb'] = $this->model_tool_image->resize($product_info['image'], 200, 200);
        } else {
            $data['popup'] = $this->model_tool_image->resize('placeholder.png', 800, 800);
            $data['thumb'] = $this->model_tool_image->resize('placeholder.png', 200, 200);
        }

        // Additional images
        $data['images'] = array();
        $results = $this->model_catalog_product->getProductImages($product_id);
        foreach ($results as $result) {
            $data['images'][] = array(
                'thumb' => $this->model_tool_image->resize($result['image'], 200, 200)
            );
        }

        // Units
        $units = $this->model_catalog_product->getProductUnits($product_id);
        if ($units) {
            $data['product_units'] = $units;
            $default_unit = $this->model_catalog_product->getDefaultUnit($units);
            $data['default_unit'] = $default_unit;
            $default_unit_id = is_array($default_unit) && isset($default_unit['unit_id']) ? $default_unit['unit_id'] : 37;
        } else {
            $data['product_units'] = false;
            $data['default_unit'] = false;
            $default_unit_id = 37;
        }

        // Available quantity
        if ($default_unit_id) {
            $available_quantity = $this->model_catalog_product->getAvailableQuantityForOnline($product_id, $default_unit_id);
        } else {
            $available_quantity = $this->model_catalog_product->getAvailableQuantityForOnline($product_id, 0);
        }

        // Minimum quantity
        $minimum_quantity = max(1, (int)$product_info['minimum']);
        $initial_quantity = $minimum_quantity;
        $data['minimum'] = $minimum_quantity;

        // Price calculation with proper error handling
        try {
            $price_data = $this->model_catalog_product->getUnitPriceData($product_id, $default_unit_id, $initial_quantity, array());
            if ($price_data['success']) {
                $data['price_data'] = $price_data['price_data'];
                $data['quantity_data'] = $price_data['quantity_data'];
                $data['discount_data'] = $price_data['discount_data'] ? array($price_data['discount_data']) : array();
                $data['product_quantity_discounts'] = !empty($price_data['product_quantity_discounts']) ? $price_data['product_quantity_discounts'] : array();
                $data['product_bundles'] = !empty($price_data['product_bundles']) ? $price_data['product_bundles'] : array();
                $data['next_discount'] = $price_data['next_discount'];
            } else {
                throw new Exception($this->language->get('error_price_calculation'));
            }
        } catch (Exception $e) {
            $data['error'] = $e->getMessage();
            $data['price_data'] = array();
            $data['quantity_data'] = array(
                'minimum' => $minimum_quantity,
                'maximum' => $available_quantity,
                'current' => $initial_quantity,
                'available' => $available_quantity
            );
            $data['product_quantity_discounts'] = array();
            $data['product_bundles'] = array();
            $data['next_discount'] = null;
        }

        // Options
        $data['options'] = $this->model_catalog_product->getProductOptionsByUnit($product_id, $default_unit_id);

        // Bundles
        $data['product_bundles'] = $this->model_catalog_product->getProductBundles($product_id);

        // Recommendations
        $recommendations = $this->model_catalog_product->getProductRecommendations($product_id);
        $data['product_recommendations'] = $recommendations;

        // Text elements
        $data['button_cart'] = $this->language->get('button_cart');
        $data['button_wishlist'] = $this->language->get('button_wishlist');
        $data['button_compare'] = $this->language->get('button_compare');
        $data['button_continue'] = $this->language->get('button_continue');
        $data['text_minimum'] = sprintf($this->language->get('text_minimum'), $product_info['minimum']);
        $data['text_login'] = sprintf($this->language->get('text_login'), $this->url->link('account/login', '', true), $this->url->link('account/register', '', true));
        $data['text_invalid_quantity'] = $this->language->get('text_invalid_quantity');
        $data['text_loading'] = $this->language->get('text_loading');
        $data['text_error'] = $this->language->get('text_error');
        $data['text_you_save'] = $this->language->get('text_you_save');
        $data['text_minimum'] = $this->language->get('text_minimum');
        $data['text_buy_together'] = $this->language->get('text_buy_together');
        $data['text_discount'] = $this->language->get('text_discount');
        $data['text_current_quantity'] = $this->language->get('text_current_quantity');
        $data['text_next_discount_at'] = $this->language->get('text_next_discount_at');
        $data['text_quantity_discounts'] = $this->language->get('text_quantity_discounts');
        $data['text_upsell'] = $this->language->get('text_upsell');
        $data['text_cross_sell'] = $this->language->get('text_cross_sell');
        $data['text_select_options'] = $this->language->get('text_select_options');
        $data['text_tax_included'] = $this->language->get('text_tax_included');
        $data['entry_qty'] = $this->language->get('entry_qty');
        $data['entry_unit'] = $this->language->get('entry_unit');
        $data['text_option'] = $this->language->get('text_option');
        $data['text_select'] = $this->language->get('text_select');
        $data['text_quick_view'] = $this->language->get('text_quick_view');
        $data['text_view_full_details'] = $this->language->get('text_view_full_details');
        
        // URLs
        $data['add_to_cart'] = $this->url->link('checkout/cart/add', '', true);
        $data['add_to_wishlist'] = $this->url->link('account/wishlist/add', '', true);
        $data['cart'] = $this->url->link('common/cart/info', '', true);
        $data['product_url'] = $this->url->link('product/product', 'product_id=' . $product_id, true);

        // Render the view
        $this->response->setOutput($this->load->view('product/product_quick_view', $data));
    } else {
        // Product not found
        $this->load->language('error/not_found');
        $data['heading_title'] = $this->language->get('heading_title');
        $data['text_error'] = $this->language->get('text_error');
        $this->response->setOutput($this->load->view('error/not_found', $data));
    }
}

public function getBundleOptions() {
    $this->load->language('product/product');
    $this->load->model('catalog/product');

    $json = array();

    // تأكّد من وجود bundle_id في الطلب
    if (isset($this->request->get['bundle_id'])) {
        $bundle_id = (int)$this->request->get['bundle_id'];

        // استدعِ دالة مخصّصة في الموديل لجلب منتجات هذه الباقة
        $bundle_products = $this->model_catalog_product->getBundleProducts($bundle_id);

        if ($bundle_products) {
            $json['success'] = true;
            $json['bundle_products'] = array();

            // لكل منتج ضمن هذه الباقة، نجلب خياراته
            foreach ($bundle_products as $product) {
                $options = $this->model_catalog_product->getProductOptionsByUnit($product['product_id'],$product['unit_id']);

                $json['bundle_products'][] = array(
                    'product_id' => $product['product_id'],
                    'unit_id' => $product['unit_id'],
                    'name'       => $product['name'],
                    'options'    => $options
                );
            }
        } else {
            // لم نجد أي منتجات في هذه الباقة
            $json['error'] = $this->language->get('error_invalid_bundle');
        }
    } else {
        // لم يتم تمرير bundle_id
        $json['error'] = $this->language->get('error_missing_bundle_id');
    }

    // تجهيز الرد بصيغة JSON
    $this->response->addHeader('Content-Type: application/json');
    $this->response->setOutput(json_encode($json));
}


// for ajax call in categort product card not in product page

public function getUnitOptions() {
    $json = array('success' => false);

    if (!empty($this->request->get['product_id']) && !empty($this->request->get['unit_id'])) {
        $product_id = (int)$this->request->get['product_id'];
        $unit_id    = (int)$this->request->get['unit_id'];

        $this->load->model('catalog/product');

        // جلب الخيارات الخاصة بهذه الوحدة
        $product_options = $this->model_catalog_product->getProductOptionsByUnit($product_id, $unit_id);

        // مثال: جلب أيضًا السعر الأساسي + السعر الخاص لتحديث الـ UI
        $price   = $this->model_catalog_product->getProductUnitPrice($product_id, $unit_id);
        $special = $this->model_catalog_product->getProductUnitSpecialPrice($product_id, $unit_id);

        // تنسيق JSON
        $json['success'] = true;
        $json['options'] = array();

        foreach ($product_options as $opt) {
            $tmp = array(
                'product_option_id' => $opt['product_option_id'],
                'name'              => $opt['name'],
                'type'              => $opt['type'],
                'required'          => $opt['required'],
                'product_option_value' => array()
            );
            // تعبئة قيم الخيار
            foreach ($opt['product_option_value'] as $v) {
                // price_prefix مثلاً '+'/'-'/ '='
                // price  رقم
                $price_str = '';
                if ((float)$v['price'] != 0) {
                    // تُدخل السعر والبادئة بالطريقة التي تريد عرضها
                    $price_str = $v['price_prefix'] . $v['price'];
                }

                $tmp['product_option_value'][] = array(
                    'product_option_value_id' => $v['product_option_value_id'],
                    'name'                    => $v['name'],
                    'price'                   => $price_str, 
                    'price_prefix'            => $v['price_prefix'],
                    // إلخ
                );
            }

            $json['options'][] = $tmp;
        }

        // صياغة السعر/السعر الخاص للعرض:
        $json['price']   = '';
        $json['special'] = '';
        // لو لديك منطق الضرائب، احسبها هنا.. الخ.
        if ($price) {
            $tax_class_id = $this->model_catalog_product->getProductTaxClass($product_id);
            $price_with_tax = $this->tax->calculate($price, $tax_class_id, $this->config->get('config_tax'));
            $json['price'] = $this->currency->format($price_with_tax, $this->session->data['currency']);
        }
        if ($special > 0 && $special < $price) {
            $tax_class_id = $this->model_catalog_product->getProductTaxClass($product_id);
            $special_with_tax = $this->tax->calculate($special, $tax_class_id, $this->config->get('config_tax'));
            $json['special'] = $this->currency->format($special_with_tax, $this->session->data['currency']);
        }
    } else {
        $json['error'] = 'Missing product_id or unit_id!';
    }

    $this->response->addHeader('Content-Type: application/json');
    $this->response->setOutput(json_encode($json));
}


 
public function getUnitPrice() {
    $this->load->language('product/product');
    $this->load->model('catalog/product');

    $json = array();

    // التحقق من وجود المعطيات الأساسية: product_id و unit_id
    if (isset($this->request->post['product_id']) && isset($this->request->post['unit_id'])) {
        try {
            $product_id = (int)$this->request->post['product_id'];
            $unit_id    = (int)$this->request->post['unit_id'];
            $quantity   = isset($this->request->post['quantity']) ? (int)$this->request->post['quantity'] : 1;

            // الخيارات الأساسية للمنتج
            $options = isset($this->request->post['options']) ? $this->request->post['options'] : array();

            // الباقات المختارة
            $selected_bundles = array();
            if (!empty($this->request->post['selected_bundles']) && is_array($this->request->post['selected_bundles'])) {
                $selected_bundles = $this->request->post['selected_bundles'];
            }

            // خيارات الباقات الداخلية
            $bundle_options = array();
            if (!empty($this->request->post['bundle_options']) && is_array($this->request->post['bundle_options'])) {
                $bundle_options = $this->request->post['bundle_options'];
            }

            // الآن حساب السعر من الموديل
            $price_data = $this->model_catalog_product->getUnitPriceData(
                $product_id,
                $unit_id,
                $quantity,
                $options,
                $selected_bundles,
                $bundle_options
            );

            $json = $price_data;

        } catch (Exception $e) {
            $json['error'] = $e->getMessage();
        }
    } else {
        $json['error'] = $this->language->get('error_required_data');
    }

    $this->response->addHeader('Content-Type: application/json');
    $this->response->setOutput(json_encode($json));
}




		// مثال على دالة تنسيق رسالة الخصم التالي
		protected function formatNextDiscountMessage($next_discount, $current_quantity) {
			$remaining = $next_discount['buy_quantity'] - $current_quantity;

			if ($next_discount['type'] === 'buy_x_get_y') {
				return sprintf(
					$this->language->get('text_add_more_free'),
					$remaining,
					$next_discount['get_quantity']
				);
			} else {
				$discount_text = $next_discount['discount_type'] === 'percentage' 
					? $next_discount['discount_value'] . '%' 
					: $this->currency->format($next_discount['discount_value'], $this->session->data['currency']);

				return sprintf(
					$this->language->get('text_add_more_discount'),
					$remaining,
					$discount_text
				);
			}
		}
	
/**
 * Helper function to get the tax rate for a given tax_class_id
 */
private function getTaxRate($tax_class_id) {
    $tax_query = $this->db->query("SELECT tr.rate FROM " . DB_PREFIX . "tax_rate tr 
        JOIN " . DB_PREFIX . "tax_rule trr ON (tr.tax_rate_id = trr.tax_rate_id) 
        WHERE trr.tax_class_id = '" . (int)$tax_class_id . "' 
        ORDER BY trr.priority ASC LIMIT 1");

    if ($tax_query->num_rows) {
        return (float)$tax_query->row['rate'];
    }

    return 0;
}



    public function getAvProductOptions() {
        $json = array();

        if (isset($this->request->get['product_id'])) {
            $product_id = (int)$this->request->get['product_id'];

            $this->load->model('catalog/product');
            $options = $this->model_catalog_product->getProductOptions($product_id);

            // فقط الخيارات المتاحة (ذات الكميات المتوفرة)
            $available_options = array();

            foreach ($options as $option) {
                $product_option_value_data = array();
                foreach ($option['product_option_value'] as $option_value) {
                    if ($option_value['quantity'] > 0) {
                        $product_option_value_data[] = array(
                            'product_option_value_id' => $option_value['product_option_value_id'],
                            'option_value_id'         => $option_value['option_value_id'],
                            'name'                    => $option_value['name'],
                            'price'                   => $this->currency->format(
                                $this->tax->calculate(
                                    $option_value['price'], 
                                    $this->config->get('config_tax_class_id'), 
                                    $this->config->get('config_tax')
                                ), 
                                $this->session->data['currency']
                            ),
                            'price_raw'               => $option_value['price'],
                            'price_prefix'            => $option_value['price_prefix']
                        );
                    }
                }
                if ($product_option_value_data) {
                    $available_options[] = array(
                        'product_option_id'    => $option['product_option_id'],
                        'option_id'            => $option['option_id'],
                        'name'                 => $option['name'],
                        'type'                 => $option['type'],
                        'required'             => $option['required'],
                        'product_option_value' => $product_option_value_data
                    );
                }
            }

            $json['options'] = $available_options;
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    // دوال مساعدة
    public function getDefaultUnit($units) {
        foreach ($units as $unit) {
            if ($unit['unit_type'] == 'base') {
                return $unit;
            }
        }
        return isset($units[0]) ? $units[0] : [
            'unit_id' => 37,
            'unit_type' => 'base',
            'conversion_factor' => 1,
            'unit_name' => 'Default Unit'
        ];
    }

    // دالة لعرض التقييمات
    public function review() {
        $this->load->language('product/product');

        $this->load->model('catalog/review');

        if (isset($this->request->get['page'])) {
            $page = (int)$this->request->get['page'];
        } else {
            $page = 1;
        }

        $data['reviews'] = array();

        $review_total = $this->model_catalog_review->getTotalReviewsByProductId($this->request->get['product_id']);

        $results = $this->model_catalog_review->getReviewsByProductId($this->request->get['product_id'], ($page - 1) * 5, 5);

        foreach ($results as $result) {
            $data['reviews'][] = array(
                'author'     => $result['author'],
                'text'       => nl2br($result['text']),
                'rating'     => (int)$result['rating'],
                'date_added' => date($this->language->get('date_format_short'), strtotime($result['date_added']))
            );
        }

        $pagination = new Pagination();
        $pagination->total = $review_total;
        $pagination->page = $page;
        $pagination->limit = 5;
        $pagination->url = $this->url->link('product/product/review', 'product_id=' . $this->request->get['product_id'] . '&page={page}');

        $data['pagination'] = $pagination->render();

        $data['results'] = sprintf($this->language->get('text_pagination'), 
            ($review_total) ? (($page - 1) * 5) + 1 : 0, 
            ((($page - 1) * 5) > ($review_total - 5)) ? $review_total : ((($page - 1) * 5) + 5), 
            $review_total, 
            ceil($review_total / 5));

        $this->response->setOutput($this->load->view('product/review', $data));
    }

    // دالة لكتابة مراجعة
    public function write() {
        $this->load->language('product/product');

        $json = array();

        if (isset($this->request->get['product_id']) && $this->request->get['product_id']) {
            if ($this->request->server['REQUEST_METHOD'] == 'POST') {
                if ((utf8_strlen($this->request->post['name']) < 3) || 
                    (utf8_strlen($this->request->post['name']) > 25)) {
                    $json['error'] = $this->language->get('error_name');
                }

                if ((utf8_strlen($this->request->post['text']) < 25) || 
                    (utf8_strlen($this->request->post['text']) > 1000)) {
                    $json['error'] = $this->language->get('error_text');
                }

                if (empty($this->request->post['rating']) || 
                    $this->request->post['rating'] < 0 || 
                    $this->request->post['rating'] > 5) {
                    $json['error'] = $this->language->get('error_rating');
                }

                // Captcha
                if ($this->config->get('captcha_' . $this->config->get('config_captcha') . '_status') && 
                    in_array('review', (array)$this->config->get('config_captcha_page'))) {
                    $captcha = $this->load->controller('extension/captcha/' . 
                        $this->config->get('config_captcha') . '/validate');

                    if ($captcha) {
                        $json['error'] = $captcha;
                    }
                }

                if (!isset($json['error'])) {
                    $this->load->model('catalog/review');

                    $this->model_catalog_review->addReview($this->request->get['product_id'], $this->request->post);

                    $json['success'] = $this->language->get('text_success');
                }
            }
        } else {
            $json['error'] = $this->language->get('error_product');
        } 

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    
}
