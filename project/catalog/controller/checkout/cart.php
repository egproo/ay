<?php
class ControllerCheckoutCart extends Controller {
    /**
     * إضافة منتج (رئيسي + باقة) إلى السلة (Ajax)
     */
    public function add(): void {
        $this->load->language('checkout/cart');
        $json = [];

        // 1) اجلب المعطيات الأساسية من POST
        $product_id = isset($this->request->post['product_id']) ? (int)$this->request->post['product_id'] : 0;
        $quantity   = isset($this->request->post['quantity'])   ? (int)$this->request->post['quantity']   : 1;

        // خيارات المنتج الرئيسي
        if (isset($this->request->post['options'])) {
            // من حقل 'options' (JSON بأسماء مفاتيح options)
            $option = $this->request->post['options'];
        } elseif (isset($this->request->post['option'])) {
            // من حقل 'option' القياسي
            $option = array_filter($this->request->post['option']);
        } else {
            $option = [];
        }

        // حقل الوحدة (إن وُجد)
        $unit_id = !empty($this->request->post['unit_id']) ? (int)$this->request->post['unit_id'] : 0;

        // حقول إضافية (مثال): الخصم حسب الكمية - pqd_id
        $pqd_id = !empty($this->request->post['product_quantity_discount_id'])
            ? (int)$this->request->post['product_quantity_discount_id']
            : null;

        // bundle_id (إن وُجد)
        $bundle_id = !empty($this->request->post['bundle_id'])
            ? (int)$this->request->post['bundle_id']
            : null;

        // لو أردت حقول لـ "selected_bundles" و "bundle_options"
        $selected_bundles = !empty($this->request->post['selected_bundles'])
            ? $this->request->post['selected_bundles']
            : [];
        $bundle_options   = !empty($this->request->post['bundle_options'])
            ? $this->request->post['bundle_options']
            : [];

        // جلب بيانات المنتج من الـModel
        $this->load->model('catalog/product');
        $product_info = $this->model_catalog_product->getProduct($product_id);

        if (!$product_info) {
            // المنتج غير موجود
            $json['error']['warning'] = $this->language->get('error_product');
            $json['redirect'] = $this->url->link('common/home');
        } else {
            // تحقق من توفر الكمية
            $available_quantity = $this->model_catalog_product->getAvailableQuantityForOnline($product_id, $unit_id);
            if ($quantity > $available_quantity) {
                $json['error']['quantity'] = sprintf($this->language->get('error_quantity'), $available_quantity);
            }

            // تحقق من الخيارات المطلوبة
            $product_options = $this->model_catalog_product->getProductOptionsByUnit($product_id, $unit_id);
            foreach ($product_options as $po) {
                if ($po['required'] && empty($option[$po['product_option_id']])) {
                    $json['error']['option_' . $po['product_option_id']] = sprintf(
                        $this->language->get('error_required'),
                        $po['name']
                    );
                }
            }

            // معالجة منتجات الباقة (إن وُجدت في POST)
            $bundle_products = [];
            if (!empty($this->request->post['bundle_products']) && is_array($this->request->post['bundle_products'])) {
                $bundle_products = $this->request->post['bundle_products'];
            }

            // فحص منتجات الباقة
            $bundle_errors = [];
            if ($bundle_products) {
                foreach ($bundle_products as $idx => $bp) {
                    $bp_pid  = !empty($bp['product_id']) ? (int)$bp['product_id'] : 0;
                    $bp_qty  = !empty($bp['quantity'])   ? (int)$bp['quantity']   : 1;
                    $bp_uid  = !empty($bp['unit_id'])    ? (int)$bp['unit_id']    : 0;
                    $bp_opts = !empty($bp['options'])    ? $bp['options']         : [];

                    // تأكد من المنتج
                    $bp_info = $this->model_catalog_product->getProduct($bp_pid);
                    if (!$bp_info) {
                        $bundle_errors[] = "Bundle item #{$idx} (product_id={$bp_pid}) not found!";
                        continue;
                    }
                    // تحقق من الكمية
                    $bp_avail = $this->model_catalog_product->getAvailableQuantityForOnline($bp_pid, $bp_uid);
                    if ($bp_qty > $bp_avail) {
                        $bundle_errors[] = "Not enough stock for bundle item #{$idx} => needed=$bp_qty, available=$bp_avail";
                    }
                    // تحقق من الخيارات المطلوبة
                    $bp_popts = $this->model_catalog_product->getProductOptionsByUnit($bp_pid, $bp_uid);
                    foreach ($bp_popts as $bp_opt) {
                        if ($bp_opt['required'] && empty($bp_opts[$bp_opt['product_option_id']])) {
                            $bundle_errors[] = "Missing required option ({$bp_opt['name']}) for item #{$idx} (product_id={$bp_pid}).";
                        }
                    }
                }
            }

            if ($bundle_errors) {
                $json['error']['bundle_warning'] = implode("\n", $bundle_errors);
            }

            // لو لم توجد أخطاء
            if (!$json) {
                // ننشئ group_id فريد للربط (ليكون نفس المنتج الرئيسي + باقاته)
                // يمكنك استخدام uniqid() أو أي Logic آخر
                $group_id = $product_id;

                // --- [1] احصل على بيانات السعر من الدالة getUnitPriceData
                //     (قد يشمل الضريبة/خصومات الكمية...الخ)
                $price_data_main = $this->model_catalog_product->getUnitPriceData(
                    $product_id,
                    $unit_id,
                    $quantity,
                    $option,
                    $selected_bundles,
                    $bundle_options
                );

                // هنا نستخدم السعر الصافي بدون الضريبة
                //  final_price_no_tax > value
                $net_price_main = (float)$price_data_main['price_data']['final_price_no_tax']['value'];

                // --- [2] أضف المنتج الرئيسي للسلة بسعر صافي
                $this->cart->add(
                    $product_id,
                    $quantity,
                    $option,
                    $unit_id,
                    $net_price_main, // السعر الصافي (بدون ضريبة)
                    false,           // is_free؟
                    $bundle_id,
                    $pqd_id,
                    $group_id,
                    $selected_bundles,
                    $bundle_options
                );

                // --- [3] إضافة منتجات الباقة (إن وُجدت)
                if ($bundle_products) {
                    foreach ($bundle_products as $bp) {
                        $bp_pid  = (int)($bp['product_id'] ?? 0);
                        $bp_qty  = (int)($bp['quantity']   ?? 1);
                        $bp_uid  = (int)($bp['unit_id']    ?? 0);
                        $bp_opts = !empty($bp['options'])  ? $bp['options'] : [];
                        $is_free = !empty($bp['is_free']);

                        // احصل على سعر المنتج (الباقة) صافيًا أيضًا
                        $bp_data = $this->model_catalog_product->getUnitPriceData(
                            $bp_pid,
                            $bp_uid,
                            $bp_qty,
                            $bp_opts,
                            [],  // لا نمرر باقات إضافية
                            []   // ولا خيارات باقات
                        );

                        // من price_data => final_price_no_tax
                        $bp_net = (float)$bp_data['price_data']['final_price_no_tax']['value'];

                        // لو المنتج مجاني في الباقة
                        if ($is_free) {
                            $bp_net = 0.0;
                        }

                        // أضف منتج الباقة إلى السلة
                        $this->cart->add(
                            $bp_pid,
                            $bp_qty,
                            $bp_opts,
                            $bp_uid,
                            $bp_net,
                            $is_free,
                            $bundle_id,  // أو null
                            null,        // pqd_id
                            $group_id
                        );
                    }
                }

                // --- [4] رسالة نجاح + تحديث شحن/دفع
                $this->load->language('checkout/cart');

                $json['success'] = sprintf(
                    $this->language->get('text_success'),
                    $this->url->link('product/product','product_id=' . $product_id),
                    $product_info['name'],
                    $this->url->link('checkout/cart')
                );

                $json['total'] = sprintf(
                    $this->language->get('text_items'),
                    $this->cart->countProducts()
                    + (!empty($this->session->data['vouchers']) ? count($this->session->data['vouchers']) : 0),
                    $this->currency->format($this->cart->getTotal(), $this->session->data['currency'])
                );

                // إعادة تهيئة الشحن والدفع
                unset($this->session->data['shipping_method']);
                unset($this->session->data['shipping_methods']);
                unset($this->session->data['payment_method']);
                unset($this->session->data['payment_methods']);

            } else {
                // وجدنا أخطاء => إعادة توجيه لصفحة المنتج
                $json['redirect'] = $this->url->link('product/product', 'product_id=' . $product_id);
            }
        }

        // خرج JSON
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    /**
     * صفحة السلة
     */
    public function index(): void {
        $this->load->language('checkout/cart');
        $this->document->setTitle($this->language->get('heading_title'));

        $data['breadcrumbs'] = [];
        $data['breadcrumbs'][] = [
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/home')
        ];
        $data['breadcrumbs'][] = [
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('checkout/cart')
        ];

        // تحقق من وجود منتجات
        if ($this->cart->hasProducts() || !empty($this->session->data['vouchers'])) {
            // أخطاء المخزون
            if (!$this->cart->hasStock()
                && (!$this->config->get('config_stock_checkout') || $this->config->get('config_stock_warning'))
            ) {
                $data['error_warning'] = $this->language->get('error_stock');
            } elseif (!empty($this->session->data['error'])) {
                $data['error_warning'] = $this->session->data['error'];
                unset($this->session->data['error']);
            } else {
                $data['error_warning'] = '';
            }

            // اشعار تسجيل الدخول لعرض الأسعار (إن كان مطلوبًا)
            if ($this->config->get('config_customer_price') && !$this->customer->isLogged()) {
                $data['attention'] = sprintf(
                    $this->language->get('text_login'),
                    $this->url->link('account/login'),
                    $this->url->link('account/register')
                );
            } else {
                $data['attention'] = '';
            }

            // رسالة نجاح
            if (isset($this->session->data['success'])) {
                $data['success'] = $this->session->data['success'];
                unset($this->session->data['success']);
            } else {
                $data['success'] = '';
            }

            // وزن السلة (لو مطلوب)
            if ($this->config->get('config_cart_weight')) {
                $data['weight'] = $this->weight->format(
                    $this->cart->getWeight(),
                    $this->config->get('config_weight_class_id'),
                    $this->language->get('decimal_point'),
                    $this->language->get('thousand_point')
                );
            } else {
                $data['weight'] = '';
            }

            $data['products'] = [];

            $this->load->model('tool/image');
            $this->load->model('tool/upload');
            $this->load->model('catalog/product');

            $products = $this->cart->getProducts();

            foreach ($products as $product) {
                // جمع نفس الـ product_id (للتحقق من الحد الأدنى)
                $product_total = 0;
                foreach ($products as $p2) {
                    if ($p2['product_id'] == $product['product_id']) {
                        $product_total += $p2['quantity'];
                    }
                }

                // تحقق الحد الأدنى
                if ($product['minimum'] > $product_total) {
                    $data['error_warning'] = sprintf(
                        $this->language->get('error_minimum'),
                        $product['name'],
                        $product['minimum']
                    );
                }

                // تجهيز الصورة
                if (!empty($product['image'])) {
                    $image = $this->model_tool_image->resize(
                        $product['image'],
                        $this->config->get('theme_' . $this->config->get('config_theme') . '_image_cart_width'),
                        $this->config->get('theme_' . $this->config->get('config_theme') . '_image_cart_height')
                    );
                } else {
                    $image = '';
                }

                // خيارات للعرض
                $option_data = [];
                if (!empty($product['option'])) {
                    foreach ($product['option'] as $opt) {
                        $val_str = '';
                        if ($opt['type'] !== 'file') {
                            $val_str = $opt['value'];
                        } else {
                            $upload_info = $this->model_tool_upload->getUploadByCode($opt['value']);
                            if ($upload_info) {
                                $val_str = $upload_info['name'];
                            }
                        }

                        // تقصير القيمة لو طويلة جدًا
                        $val_str_short = (utf8_strlen($val_str) > 20)
                            ? utf8_substr($val_str, 0, 20) . '...'
                            : $val_str;

                        $option_data[] = [
                            'name'  => $opt['name'],
                            'value' => $val_str_short
                        ];
                    }
                }

                // السعر الصافي (المخزن في السلة) => نضيف عليه الضريبة للعرض
                $net_price    = (float)$product['price'];
                $tax_class_id = $product['tax_class_id'];

                if ($this->customer->isLogged() || !$this->config->get('config_customer_price')) {
                    // حساب الضريبة للسعر الصافي
                    $gross = $this->tax->calculate(
                        $net_price,
                        $tax_class_id,
                        $this->config->get('config_tax')
                    );
                    $price = $this->currency->format($gross, $this->session->data['currency']);
                    $total = $this->currency->format($gross * $product['quantity'], $this->session->data['currency']);
                } else {
                    $price = false;
                    $total = false;
                }

                // اسم الوحدة
                $unit_name = '';
                if (!empty($product['unit_id'])) {
                    $unit_name = $this->model_catalog_product->getUnitName($product['unit_id']);
                }

                // لو مجاني
                $free_text = '';
                if (!empty($product['is_free'])) {
                    $free_text = ' <span class="text-success">(' . $this->language->get('text_free') . ')</span>';
                }

                $data['products'][] = [
                    'cart_id'   => $product['cart_id'],
                    'thumb'     => $image,
                    'name'      => $product['name'] . $free_text,
                    'model'     => $product['model'],
                    'option'    => $option_data,
                    'quantity'  => $product['quantity'],
                    'unit'      => $unit_name,
                    'stock'     => $product['stock']
                        ? true
                        : !(!$this->config->get('config_stock_checkout')
                            || $this->config->get('config_stock_warning')),
                    'reward'    => !empty($product['reward'])
                        ? sprintf($this->language->get('text_points'), $product['reward'])
                        : '',
                    'price'     => $price,
                    'total'     => $total,
                    // نعرض group_id للديباغ أو للاستخدام (لو أردت)
                    'group_id'  => !empty($product['group_id']) ? $product['group_id'] : '',
                    'href'      => $this->url->link('product/product','product_id=' . $product['product_id'])
                ];
            }

            // القسائم (vouchers)

            // القسائم (vouchers)
            $data['vouchers'] = [];
            if (!empty($this->session->data['vouchers'])) {
                foreach ($this->session->data['vouchers'] as $key => $voucher) {
                    $data['vouchers'][] = [
                        'key'         => $key,
                        'description' => $voucher['description'],
                        'amount'      => $this->currency->format($voucher['amount'], $this->session->data['currency']),
                        'remove'      => $this->url->link('checkout/cart', 'remove=' . $key)
                    ];
                }
            }

            // حساب الإجمالي لأسفل الصفحة
            $this->load->model('setting/extension');
            $total  = 0;
            $totals = [];
            $taxes  = $this->cart->getTaxes();

            $total_data = [
                'totals' => &$totals,
                'taxes'  => &$taxes,
                'total'  => &$total
            ];

            if ($this->customer->isLogged() || !$this->config->get('config_customer_price')) {
                $sort_order = [];
                $results = $this->model_setting_extension->getExtensionsByType('total');

                foreach ($results as $k => $v) {
                    $sort_order[$k] = $this->config->get('total_' . $v['code'] . '_sort_order');
                }
                array_multisort($sort_order, SORT_ASC, $results);

                foreach ($results as $result) {
                    if ($this->config->get('total_' . $result['code'] . '_status')) {
                        $this->load->model('extension/total/' . $result['code']);
                        $this->{'model_extension_total_' . $result['code']}->getTotal($total_data);
                    }
                }

                $sort_order = [];
                foreach ($totals as $k => $val) {
                    $sort_order[$k] = $val['sort_order'];
                }
                array_multisort($sort_order, SORT_ASC, $totals);
            }

            $data['totals'] = [];
            foreach ($totals as $tt) {
                $data['totals'][] = [
                    'title' => $tt['title'],
                    'text'  => $this->currency->format($tt['value'], $this->session->data['currency'])
                ];
            }
            
            // روابط الإجراءات
            $data['action']   = $this->url->link('checkout/cart/edit');
            $data['continue'] = $this->url->link('common/home');
            $data['checkout'] = $this->url->link('checkout/checkout');

            // أجزاء الصفحة
            $data['column_left']    = $this->load->controller('common/column_left');
            $data['column_right']   = $this->load->controller('common/column_right');
            $data['content_top']    = $this->load->controller('common/content_top');
            $data['content_bottom'] = $this->load->controller('common/content_bottom');
            $data['footer']         = $this->load->controller('common/footer');
            $data['header']         = $this->load->controller('common/header');

            // إخراج
            $this->response->setOutput($this->load->view('checkout/cart', $data));
        } else {
            // لا توجد منتجات => وجه نحو صفحة فارغة أو الرئيسية
            unset($this->session->data['success']);
            $data['continue']   = $this->url->link('common/home');
            $data['text_error'] = $this->language->get('text_no_results');

            $data['column_left']    = $this->load->controller('common/column_left');
            $data['column_right']   = $this->load->controller('common/column_right');
            $data['content_top']    = $this->load->controller('common/content_top');
            $data['content_bottom'] = $this->load->controller('common/content_bottom');
            $data['footer']         = $this->load->controller('common/footer');
            $data['header']         = $this->load->controller('common/header');

            $this->response->setOutput($this->load->view('error/not_found', $data));
        }
    }

    /**
     * تعديل كمية المنتج من صفحة السلة
     */
    public function edit(): void {
        $this->load->language('checkout/cart');
        $json = [];

        $key      = !empty($this->request->post['key'])      ? (int)$this->request->post['key']      : 0;
        $quantity = !empty($this->request->post['quantity']) ? (int)$this->request->post['quantity'] : 1;

        if ($key && $quantity > 0) {
            // جلب المنتج من السلة
            $cart_product = $this->cart->getProduct($key);
            if ($cart_product) {
                $this->load->model('catalog/product');

                // تحقق من الكمية
                $available_qty = $this->model_catalog_product->getAvailableQuantityForOnline(
                    $cart_product['product_id'],
                    $cart_product['unit_id']
                );
                if ($quantity > $available_qty) {
                    $json['error'] = sprintf($this->language->get('error_quantity'), $available_qty);
                } else {
                    // احسب السعر الجديد بالاستعانة بـ getUnitPriceData
                    $price_data = $this->model_catalog_product->getUnitPriceData(
                        $cart_product['product_id'],
                        $cart_product['unit_id'],
                        $quantity,
                        // إعادة بناء خيارات الـ cart_product
                        $this->rebuildOptionArray($cart_product['option']),
                        // استخدم selected_bundles, bundle_options من سلة المنتج
                        $cart_product['selected_bundles'] ?? [],
                        $cart_product['bundle_options']   ?? []
                    );

                    // نريد السعر الصافي
                    $new_net_price = (float)$price_data['price_data']['final_price_no_tax']['value'];

                    // حدِّث في السلة
                    $this->cart->update($key, $quantity, $new_net_price);
                    $json['success'] = $this->language->get('text_edit');
                }
            } else {
                $json['error'] = $this->language->get('error_product');
            }

            // إعادة تهيئة بيانات الشحن والدفع
            $this->session->data['success'] = $this->language->get('text_remove');
            unset($this->session->data['shipping_method']);
            unset($this->session->data['shipping_methods']);
            unset($this->session->data['payment_method']);
            unset($this->session->data['payment_methods']);
            unset($this->session->data['reward']);

            // إعادة توجيه للسلة
            $this->response->redirect($this->url->link('checkout/cart'));
        }

        // خرج JSON
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    /**
     * دالة مساعدة لإعادة بناء مصفوفة خيارات المنتج (option_ids فقط)
     */
    protected function rebuildOptionArray($option_data) {
        $rebuilt = [];
        if (!is_array($option_data)) {
            return $rebuilt;
        }

        foreach ($option_data as $opt) {
            $po_id  = !empty($opt['product_option_id'])       ? (int)$opt['product_option_id']       : 0;
            $pov_id = !empty($opt['product_option_value_id']) ? $opt['product_option_value_id']       : 0;

            // لو نوع checkbox قد يحوي عدة قيم في array
            if (is_array($pov_id)) {
                $rebuilt[$po_id] = $pov_id;
            } else {
                $rebuilt[$po_id] = $pov_id;
            }
        }
        return $rebuilt;
    }

    /**
     * إزالة منتج/مجموعة من السلة
     */
    public function remove(): void {
        $this->load->language('checkout/cart');
        $json = [];

        $key = !empty($this->request->post['key']) ? (int)$this->request->post['key'] : 0;

        if (!$json) {
            // نجلب صف السلة بالـcart_id
            $cart_item = $this->cart->chekGroup($key);

            // لو للمنتج group_id => نحذف كامل المجموعة
            if ($cart_item && !empty($cart_item['group_id'])) {
                $this->cart->removeByGroup($cart_item['group_id']);
            } else {
                // حذف عادي
                $this->cart->remove($key);
            }

            if ($this->cart->hasProducts() || !empty($this->session->data['vouchers'])) {
                $json['success'] = $this->language->get('text_remove');
            } else {
                // إن أصبحت السلة فارغة
                $json['redirect'] = $this->url->link('checkout/cart', '', true);
            }

            // نظافة إضافية
            unset($this->session->data['shipping_method']);
            unset($this->session->data['shipping_methods']);
            unset($this->session->data['payment_method']);
            unset($this->session->data['payment_methods']);
            unset($this->session->data['reward']);
        }

        // خرج JSON
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
}
