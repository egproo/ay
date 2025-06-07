
<?php
class ModelCatalogProduct extends Model {


public function getDefaultUnit($units) {
    // Si units es falso o array vacío, retornar un valor predeterminado
    if (!$units || !is_array($units) || empty($units)) {
        return [
            'unit_id' => 37,
            'unit_type' => 'base',
            'conversion_factor' => 1,
            'unit_name' => 'Default Unit'
        ];
    }
    
    // Buscar unidad base primero
    foreach ($units as $unit) {
        if (isset($unit['unit_type']) && $unit['unit_type'] == 'base') {
            return $unit;
        }
    }
    
    // Si no hay unidad base, devolver la primera
    return isset($units[0]) ? $units[0] : [
        'unit_id' => 37,
        'unit_type' => 'base',
        'conversion_factor' => 1,
        'unit_name' => 'Default Unit'
    ];
}

public function getBundleProducts($bundle_id) {
    $bundle_products = array();

    // استعلام لجلب عناصر الباقة (product_bundle_item)
    $query = $this->db->query("
        SELECT pbi.product_id,
               pbi.quantity,
               pbi.is_free,
               pbi.unit_id,
               p.image,
               pd.name
        FROM " . DB_PREFIX . "product_bundle_item pbi
        LEFT JOIN " . DB_PREFIX . "product p
               ON (pbi.product_id = p.product_id)
        LEFT JOIN " . DB_PREFIX . "product_description pd
               ON (p.product_id = pd.product_id
                   AND pd.language_id = '" . (int)$this->config->get('config_language_id') . "')
        WHERE pbi.bundle_id = '" . (int)$bundle_id . "'
    ");

    // لو لم يجد أي صفوف، نعيد مصفوفة فارغة
    if (!$query->num_rows) {
        return $bundle_products;
    }

    // تمرير كل منتج في الباقة
    foreach ($query->rows as $row) {
        // جلب خيارات المنتج إن أردت عرضها مباشرة
        $options = $this->getProductOptions($row['product_id']);

        // تحضير الصورة (لو أحببت تغييره أو استخدام صورة أخرى)
        $image_path = $row['image'] ?: 'placeholder.png';
        $this->load->model('tool/image');
        $thumb = $this->model_tool_image->resize($image_path, 100, 100);

        $bundle_products[] = array(
            'product_id'  => (int)$row['product_id'],
            'name'        => $row['name'] ?: '', // احتياطًا لو كان NULL
            'image'       => $thumb,
            'quantity'    => (int)$row['quantity'],
            'unit_id'     => (int)$row['unit_id'],
            'is_free'     => (bool)$row['is_free'],
            // لو أردت الوحدة بالاسم
            'unit_name'   => $this->getUnitName($row['unit_id']),
            // لو أردت تفاصيل أخرى مثل السعر النهائي/الخاص
            // 'price'    => ...
            // 'special'  => ...
            // إلخ

            // يمكنك اختيار جلب خيارات المنتج هنا
            'options'     => $options,
        );
    }

    return $bundle_products;
}
    
// وحدة افتراضية لها مخزون
public function getxDefaultUnit($product_id, $units) {
    // 1) أوجد الـbaseUnit إن وُجد
    $baseUnit = null;
    foreach ($units as $u) {
        if ($u['unit_type'] == 'base') {
            $baseUnit = $u;
            break;
        }
    }
    // إن لم نجد BaseUnit نعتمد أول وحدة في المصفوفة
    if (!$baseUnit && $units) {
        $baseUnit = $units[0];
    }

    // 2) تحقق إن كان لدى الـbaseUnit مخزون > 0
    if ($baseUnit && $this->hasStock($product_id, $baseUnit['unit_id'])) {
        return $baseUnit;
    }

    // 3) لو الـbaseUnit ليس به مخزون
    // نبحث عن أول وحدة لديها مخزون > 0
    foreach ($units as $u) {
        if ($this->hasStock($product_id, $u['unit_id'])) {
            return $u;
        }
    }

    // 4) لو لا توجد أي وحدة متوفرة (المخزون صفر للجميع)
    // نرجع الـbaseUnit أو الأولى (على الأقل ليظهر للعميل)
    return $baseUnit ?: (count($units) ? $units[0] : null);
}

// دالة بسيطة للتحقق هل المخزون المتاح > 0
public function hasStock($product_id, $unit_id) {
    $qty = $this->model_catalog_product->getAvailableQuantityForOnline($product_id, $unit_id);
    return ($qty > 0);
}
/**
 * احسب سعر المنتج ديناميكيًا مع الوحدات والخيارات والباقات وخصومات الكمية
 * مع إضافة حساب الخيارات المختارة لمنتجات الباقة.
 *
 * @param int   $product_id       رقم المنتج الرئيسي
 * @param int   $unit_id          رقم الوحدة المحدّدة للمنتج الرئيسي
 * @param int   $quantity         الكمية المطلوبة للمنتج الرئيسي
 * @param array $options          خيارات المنتج الرئيسي (select/radio/checkbox/...)
 * @param array $selected_bundles أرقام الباقات المختارة (إن وجدت)
 * @param array $bundle_options   خيارات منتجات كل باقة؛ هيكل: [bundleId => [productId => [optionId => value(s)]]]
 *
 * @return array  يعيد مصفوفة غنية بالبيانات تشمل السعر الصافي (بدون ضريبة) والسعر النهائي (مع الضريبة)
 */
public function getUnitPriceData(
    $product_id,
    $unit_id,
    $quantity = 1,
    $options = array(),
    $selected_bundles = array(),
    $bundle_options = array()
) {
    // تحميل ملف اللغة
    $this->load->language('product/product');

    // 1) التحقق من المنتج
    $product_info = $this->getProduct($product_id);
    if (!$product_info) {
        throw new Exception($this->language->get('error_product_not_found'));
    }

    // 2) التحقق من تفاصيل الوحدة
    $unit_info = $this->getProductUnitDetails($product_id, $unit_id);
    if (!$unit_info) {
        throw new Exception($this->language->get('error_invalid_unit'));
    }

    // 3) تحديد الكمية المتاحة والحد الأدنى
    $available_quantity = $this->getAvailableQuantityForOnline($product_id, $unit_id);
    $minimum_quantity   = max(1, (int)$product_info['minimum']);

    // التأكد من أن الكمية >= الحد الأدنى
    if ($quantity < $minimum_quantity) {
        $quantity = $minimum_quantity;
    } elseif ($quantity > $available_quantity) {
        $quantity = $available_quantity;
    }

    // 4) جلب السعر الأساسي (بدون ضريبة)
    $base_price = $this->getProductUnitPrice($product_id, $unit_id);

    // 5) جلب السعر الخاص (إن وجد) والتأكد أنه أقل من الأساسي
    $special_price   = $this->getProductSpecialPrice($product_id, $unit_id);
    $has_real_special= ($special_price > 0 && $special_price < $base_price);

    // 6) جلب كل الخيارات الممكنة لهذه الوحدة
    $product_options = $this->getProductOptionsByUnit($product_id, $unit_id);

    // تحضير مصفوفة للاختيارات التي يتم التحقق منها
    $validatedSelectedValues = array();

    // 6-أ) الاحتفاظ بالاختيارات المرسلة إن كانت صالحة
    if (!empty($options) && !empty($product_options)) {
        foreach ($product_options as $opt) {
            $po_id = $opt['product_option_id'];
            if (isset($options[$po_id])) {
                // قد يكون الاختيار قيمة واحدة أو مصفوفة
                $userValues = is_array($options[$po_id])
                    ? $options[$po_id]
                    : array($options[$po_id]);

                // IDs للقيم المسموحة بهذه الوحدة
                $validOptionValueIds = array_column($opt['product_option_value'], 'product_option_value_id');

                $keptValues = array();
                // نحتفظ فقط بالقيم المرسلة التي توجد فعلاً في الخيار
                foreach ($userValues as $val) {
                    if (in_array($val, $validOptionValueIds)) {
                        $keptValues[] = $val;
                    }
                }

                // لو نوع الخيار select/radio => نأخذ أول قيمة فقط
                if (in_array($opt['type'], ['select','radio'])) {
                    if (!empty($keptValues)) {
                        $validatedSelectedValues[$po_id] = $keptValues[0];
                    }
                }
                // نوع checkbox => كل القيم (مصفوفة)
                elseif ($opt['type'] === 'checkbox') {
                    $validatedSelectedValues[$po_id] = $keptValues;
                }
                else {
                    // نص / تاريخ / وقت ...
                    $validatedSelectedValues[$po_id] = isset($keptValues[0]) ? $keptValues[0] : '';
                }
            }
        }
    }

    // 6-ب) اختيار الأقل سعراً لو لم يسبق للمستخدم اختيار
    //    في حال الخيارات select/radio
    foreach ($product_options as $opt) {
        $po_id = $opt['product_option_id'];
        if (!isset($validatedSelectedValues[$po_id]) || empty($validatedSelectedValues[$po_id])) {
            // لو لم يرسل المستخدم أي قيمة صالحة
            // في حال select/radio نختار الأقل تكلفة
            if (in_array($opt['type'], ['select','radio']) && !empty($opt['product_option_value'])) {
                $minPriceVal = null;
                $minCost     = PHP_INT_MAX;

                foreach ($opt['product_option_value'] as $val) {
                    $price  = (float)$val['price'];
                    $prefix = $val['price_prefix'];

                    // '=' يعتبر استبدال كلي للسعر
                    if ($prefix === '=') {
                        $price = -9999999; 
                    } elseif ($prefix === '-') {
                        $price = -$price;
                    }

                    if ($price < $minCost) {
                        $minCost = $price;
                        $minPriceVal = $val['product_option_value_id'];
                    }
                }
                if ($minPriceVal) {
                    $validatedSelectedValues[$po_id] = $minPriceVal;
                }
            }
        }
    }

    // 7) فصل السعرين الأساسي والخاص
    $base_price_final    = $base_price;
    $special_price_final = $special_price; 
    // سيُمثل فارق (base - special) عند الحاجة

    // حساب سعر الخيارات
    $total_option_price = 0.0;
    $selected_options   = array();

    // لو لدى المستخدم اختيارات صالحة
    if (!empty($validatedSelectedValues) && !empty($product_options)) {
        foreach ($product_options as $option) {
            $po_id = $option['product_option_id'];
            if (isset($validatedSelectedValues[$po_id])) {
                // قد يكون array للـcheckbox
                $userChoiceValues = is_array($validatedSelectedValues[$po_id])
                    ? $validatedSelectedValues[$po_id]
                    : array($validatedSelectedValues[$po_id]);

                foreach ($userChoiceValues as $value_id) {
                    // دالة getOptionPrice() تجلب سطر السعر/البادئة/الاسم
                    $option_info = $this->getOptionPrice($product_id, $po_id, $value_id);
                    if ($option_info) {
                        // لو prefix = '=' => استبدال السعر بالكامل
                        if ($option_info['price_prefix'] === '=') {
                            $base_price_final = (float)$option_info['price'];
                            // ولو هناك سعر خاص حقيقي، نحافظ على الفارق
                            if ($has_real_special) {
                                $special_price_final = max(
                                    0,
                                    $base_price_final - ($base_price - $special_price)
                                );
                            }
                            // نلغي أي إضافات سابقة
                            $total_option_price = 0.0;
                            break 2; 
                        }

                        $option_price = (float)$option_info['price'];

                        // prefix '+'
                        if ($option_info['price_prefix'] === '+') {
                            $base_price_final    += $option_price;
                            if ($has_real_special) {
                                $special_price_final += $option_price;
                            }
                            $total_option_price  += $option_price;
                        }
                        // prefix '-'
                        elseif ($option_info['price_prefix'] === '-') {
                            $base_price_final    -= $option_price;
                            if ($has_real_special) {
                                $special_price_final -= $option_price;
                            }
                            $total_option_price  -= $option_price;
                        }

                        // نضيفه لمصفوفة للعرض
                        $selected_options[] = array(
                            'name'   => $option_info['name'], // من getOptionPrice
                            'price'  => $this->currency->format($option_price, $this->session->data['currency']),
                            'prefix' => $option_info['price_prefix']
                        );
                    }
                }
            }
        }
    }

    // بعد إضافة الخيارات
    $base_price_with_options    = max(0, $base_price_final);
    $special_price_with_options = ($has_real_special)
        ? max(0, $special_price_final)
        : 0;

    // 8) حساب الباقات (بدون خصم الكمية؛ أي تُضاف كما هي)
    $bundle_items_no_tax_base    = 0.0; 
    $bundle_items_no_tax_special = 0.0;
    $bundle_details = array();

    if (!empty($selected_bundles)) {
        foreach ($selected_bundles as $bid) {
            // جلب بيانات الباقة
            $bundle_q = $this->db->query("
                SELECT * FROM " . DB_PREFIX . "product_bundle
                WHERE bundle_id = '" . (int)$bid . "'
                  AND status = '1'
            ");
            if (!$bundle_q->num_rows) continue;

            $bundle_info = $bundle_q->row;

            // جلب عناصر الباقة
            $items_q = $this->db->query("
                SELECT pbi.*, p.tax_class_id, p.image, pd.name
                FROM " . DB_PREFIX . "product_bundle_item pbi
                LEFT JOIN " . DB_PREFIX . "product p
                       ON (pbi.product_id = p.product_id)
                LEFT JOIN " . DB_PREFIX . "product_description pd
                       ON (p.product_id = pd.product_id
                           AND pd.language_id = '" . (int)$this->config->get('config_language_id') . "')
                WHERE pbi.bundle_id = '" . (int)$bid . "'
            ");
            if (!$items_q->num_rows) continue;

            $original_bundle_total = 0.0;
            $b_items = array();

            foreach ($items_q->rows as $itm) {
                $b_pid     = (int)$itm['product_id'];
                // سعر أساسي/خاص للوحدة
                $b_base    = $this->getProductUnitPrice($b_pid, $itm['unit_id']);
                $b_special = $this->getProductSpecialPrice($b_pid, $itm['unit_id']);

                // اختر (base أو special) أيهما أقل
                $b_final   = ($b_special > 0 && $b_special < $b_base) ? $b_special : $b_base;

                // =========== حساب خيارات منتج الباقة (إن وُجدت) ==========
                $selected_opts_for_item = array();
                if (!empty($bundle_options[$bid][$b_pid])) {
                    foreach ($bundle_options[$bid][$b_pid] as $b_po_id => $b_val) {
                        $valsArr = is_array($b_val) ? $b_val : array($b_val);
                        foreach ($valsArr as $b_ov_id) {
                            $opt_info = $this->getOptionPrice($b_pid, $b_po_id, $b_ov_id);
                            if ($opt_info) {
                                $opt_price = (float)$opt_info['price'];
                                $prefix    = $opt_info['price_prefix'];

                                if ($prefix === '+') {
                                    $b_final += $opt_price;
                                } elseif ($prefix === '-') {
                                    $b_final -= $opt_price;
                                } elseif ($prefix === '=') {
                                    $b_final = $opt_price;
                                }

                                // لو أردت اسم وقيمة الخيار مفصل
                                $detail = $this->getOptionDetailForBundle($b_pid, $b_po_id, $b_ov_id);
                                if ($detail) {
                                    $selected_opts_for_item[] = array(
                                        'option_name'    => $detail['option_name'],
                                        'option_value'   => $detail['option_value'],
                                        'formatted_price'=> $detail['formatted_price']
                                    );
                                } else {
                                    // أو على الأقل نسجل الاسم
                                    $selected_opts_for_item[] = array(
                                        'option_name'    => $opt_info['name'],
                                        'option_value'   => '(OV#'.$b_ov_id.')',
                                        'formatted_price'=> $prefix . $opt_price
                                    );
                                }
                            }
                        }
                    }
                }
                // =========== انتهى خيارات الباقة ===========

                // لو المنتج مجاني في الباقة
                if (!empty($itm['is_free'])) {
                    $b_final = 0;
                }

                // ضرب الكمية
                $line_original = $b_final * (int)$itm['quantity'];

                $original_bundle_total += $line_original;

                // حفظ بيانات العناصر
                $b_items[] = array(
                    'product_id' => $b_pid,
                    'name'       => $itm['name'],
                    'quantity'   => (int)$itm['quantity'],
                    'item_base_price' => $line_original, 
                    'is_free'    => (bool)$itm['is_free'],
                    'selected_options' => $selected_opts_for_item
                );
            }

            // طبق خصم الباقة إن وجد (نوع percentage / fixed)
            $bundle_discount_type  = $bundle_info['discount_type'];
            $bundle_discount_value = (float)$bundle_info['discount_value'];

            $final_b_total = $original_bundle_total;
            if ($bundle_discount_type === 'percentage') {
                $discAmt       = $original_bundle_total * ($bundle_discount_value / 100.0);
                $final_b_total = max($original_bundle_total - $discAmt, 0);
            } elseif ($bundle_discount_type === 'fixed') {
                $discAmt       = $bundle_discount_value;
                $final_b_total = max($original_bundle_total - $discAmt, 0);
            }
            // لو type=product => اعتمدنا is_free

            // أضف إجمالي الباقة لسعر الأساس
            $bundle_items_no_tax_base += $final_b_total;

            // لو لدى المنتج الرئيسي سعر خاص فعلي
            if ($has_real_special) {
                $bundle_items_no_tax_special += $final_b_total;
            }

            // حفظ بيانات الباقة
            $bundle_details[] = array(
                'bundle_id'          => $bid,
                'name'               => $bundle_info['name'],
                'discount_type'      => $bundle_discount_type,
                'discount_value'     => $bundle_discount_value,
                'items'              => $b_items,
                'original_total'     => $original_bundle_total,
                'final_bundle_total' => $final_b_total
            );
        }
    }

    // === قبل خصومات الكمية ===
    //  - نفصل المنتج الأساسي (بخيارته) عن الباقة
    //  - نطبق خصم الكمية على المنتج الرئيسي فقط
    $main_subtotal_no_tax         = $base_price_with_options; 
    $main_special_subtotal_no_tax = $has_real_special ? $special_price_with_options : 0;

    // جلب خصومات الكمية لهذا المنتج
    $quantity_discounts = $this->getProductQuantityDiscounts($product_id, $quantity, $unit_id);

    // دالة داخلية لتطبيق خصومات الكمية
    $applyDiscounts = function($price, $qty, $discounts) use ($product_info) {
        $result = $price;
        foreach ($discounts as $disc) {
            if ($qty >= $disc['buy_quantity']) {
                // نوع buy_x_get_y
                if ($disc['type'] === 'buy_x_get_y') {
                    $sets         = floor($qty / $disc['buy_quantity']);
                    $free_quantity= $sets * $disc['get_quantity'];
                    if ($qty + $free_quantity > 0) {
                        $free_val = round(($result / $qty) * $free_quantity, 2);
                        $result   = max($result - $free_val, 0);
                    }
                }
                else {
                    // discount percentage or fixed
                    if ($disc['type'] === 'percentage') {
                        $discAmt = round($result * ($disc['discount_value'] / 100.0), 2);
                    } else { // fixed
                        $discAmt = round($disc['discount_value'], 2);
                    }
                    $result = max($result - $discAmt, 0);
                }
            }
        }
        return $result;
    };

    // طبق الخصم الكمي على المنتج الأساسي فقط
    $final_main_base_no_tax    = $applyDiscounts($main_subtotal_no_tax, $quantity, $quantity_discounts);
    $final_main_special_no_tax = 0;
    if ($has_real_special) {
        $final_main_special_no_tax = $applyDiscounts($main_special_subtotal_no_tax, $quantity, $quantity_discounts);
    }

    // الآن نضيف إجمالي الباقة (بدون خصم الكمية)
    $final_base_no_tax    = $final_main_base_no_tax    + $bundle_items_no_tax_base;
    $final_special_no_tax = $has_real_special
        ? ($final_main_special_no_tax + $bundle_items_no_tax_special)
        : 0;

    // 10) حساب الضريبة
    $tax_class_id    = (int)$product_info['tax_class_id'];
    $base_with_tax   = $this->tax->calculate($final_base_no_tax, $tax_class_id, true);
    $special_with_tax= $has_real_special
        ? $this->tax->calculate($final_special_no_tax, $tax_class_id, true)
        : 0;

    // 11) التوفير
    $raw_savings = 0;
    if ($has_real_special) {
        $raw_savings = round($base_with_tax - $special_with_tax, 2);
        if ($raw_savings < 0) {
            $raw_savings = 0;
        }
    }

    // نسبة الخصم
    $discount_percentage = 0;
    if ($base_with_tax > 0 && $raw_savings > 0) {
        $discount_percentage = round(($raw_savings / $base_with_tax) * 100);
    }

    // 12) حساب سعر المنتج الواحد بالضريبة
    $unit_price_price   = round($base_with_tax, 2);
    $unit_price_special = round($special_with_tax, 2);

    // 13) إعداد current_quantity لكل خصم لعرضه
    $enhanced_discounts = [];
    foreach ($quantity_discounts as $disc) {
        $disc['current_quantity'] = $quantity;
        $enhanced_discounts[] = $disc;
    }

    // نضيف أيضًا قيم السعر الصافي (بدون ضريبة) للقطعة الواحدة:
    $unit_price_no_tax = ($unit_price_special > 0)
        ? $final_special_no_tax  // special
        : $final_base_no_tax;    // أو الأساسي

    // 14) تجهيز الرد النهائي
    return [
        'success' => true,

        // بيانات السعر (قبل/بعد الضريبة والخصومات)
        'price_data' => [
            // 1) السعر الأساسي للوحدة بدون خيارات
            'base_price' => [
                'value'     => round($base_price, 2),
                'formatted' => $this->currency->format(
                    round($base_price, 2),
                    $this->session->data['currency']
                )
            ],
            // 2) السعر بعد إضافة خيارات المنتج
            'base_price_with_options' => [
                'value'     => round($base_price_with_options, 2),
                'formatted' => $this->currency->format(
                    round($base_price_with_options, 2),
                    $this->session->data['currency']
                )
            ],
            // 3) السعر مع خياراته + الضريبة
            'base_price_with_options_with_tax' => [
                'value'     => round($this->tax->calculate($base_price_with_options, $tax_class_id, true), 2),
                'formatted' => $this->currency->format(
                    round($this->tax->calculate($base_price_with_options, $tax_class_id, true), 2),
                    $this->session->data['currency']
                )
            ],
            // 4) السعر الخاص دون حساب الخيارات (يُمكن أن يكون 0 إن لم يوجد special)
            'special_price' => [
                'value'     => $has_real_special ? $special_price : 0,
                'formatted' => $has_real_special
                    ? $this->currency->format($special_price, $this->session->data['currency'])
                    : '',
                'discount_percentage' => $discount_percentage
            ],
            // 5) السعر الخاص + تكلفة الخيارات
            'special_price_with_option' => [
                'value'     => $has_real_special ? $special_price + $total_option_price : 0,
                'formatted' => $has_real_special
                    ? $this->currency->format($special_price + $total_option_price, $this->session->data['currency'])
                    : '',
                'discount_percentage' => $discount_percentage
            ],
            // 6) السعر الخاص + الخيارات + الضريبة
            'special_price_with_option_with_tax' => [
                'value'     => $has_real_special
                    ? $this->tax->calculate($special_price, $tax_class_id, true)
                      + $this->tax->calculate($total_option_price, $tax_class_id, true)
                    : 0,
                'formatted' => $has_real_special
                    ? $this->currency->format(
                          $this->tax->calculate($special_price, $tax_class_id, true)
                        + $this->tax->calculate($total_option_price, $tax_class_id, true),
                          $this->session->data['currency']
                      )
                    : '',
                'discount_percentage' => $discount_percentage
            ],
            // 7) السعر الحالي (سواء special أو base) *مع الضريبة*
            'current_price' => [
                'value'     => ($unit_price_special > 0) ? $unit_price_special : $unit_price_price,
                'formatted' => $this->currency->format(
                    ($unit_price_special > 0) ? $unit_price_special : $unit_price_price,
                    $this->session->data['currency']
                )
            ],
            // 8) معلومات السعر الكلّي للخيارات
            'option_price' => [
                'total_price'          => round($total_option_price, 2),
                'total_price_with_tax' => round($this->tax->calculate($total_option_price, $tax_class_id, true), 2),
                'selected_options'     => $selected_options
            ],
            // مقدار الضريبة بشكل تقريبي
            'tax_amount' => [
                'value' => round(
                    (($unit_price_special > 0) ? $unit_price_special : $unit_price_price)
                    - (($unit_price_special > 0) ? $final_special_no_tax : $final_base_no_tax),
                    2
                ),
                'formatted' => $this->currency->format(
                    round(
                        (($unit_price_special > 0) ? $unit_price_special : $unit_price_price)
                        - (($unit_price_special > 0) ? $final_special_no_tax : $final_base_no_tax),
                        2
                    ),
                    $this->session->data['currency']
                )
            ],
            // 9) السعر النهائي بعد الضريبة (للقطعة)
            'final_price' => [
                'value'     => ($unit_price_special > 0) ? $unit_price_special : $unit_price_price,
                'formatted' => $this->currency->format(
                    ($unit_price_special > 0) ? $unit_price_special : $unit_price_price,
                    $this->session->data['currency']
                )
            ],
            // 10) السعر الصافي للقطعة دون الضريبة
            'final_price_no_tax' => [
                'value'     => round($unit_price_no_tax, 2),
                'formatted' => $this->currency->format(
                    round($unit_price_no_tax, 2),
                    $this->session->data['currency']
                )
            ],
            // 11) إجمالي السعر الصافي عند مضاعفة بالكمية
            'total_price_no_tax' => [
                'value'     => round($unit_price_no_tax * $quantity, 2),
                'formatted' => $this->currency->format(
                    round($unit_price_no_tax * $quantity, 2),
                    $this->session->data['currency']
                )
            ],
            // 12) مقدار التوفير
            'savings' => [
                'amount'     => $raw_savings,
                'formatted'  => $this->currency->format($raw_savings, $this->session->data['currency']),
                'percentage' => $discount_percentage
            ],
            // 13) كائن مُختصر للسعر
            'unit_price' => [
                'price'             => $unit_price_price,
                'special'           => $unit_price_special,
                'price_formatted'   => $this->currency->format($unit_price_price, $this->session->data['currency']),
                'special_formatted' => ($unit_price_special > 0)
                    ? $this->currency->format($unit_price_special, $this->session->data['currency'])
                    : '',
                'discount_percentage' => $discount_percentage
            ],
            // 14) إجمالي السعر النهائي (للـquantity) مع الضريبة
            'total_price' => [
                'value' => round(
                    (($unit_price_special > 0) ? $unit_price_special : $unit_price_price) * $quantity,
                    2
                ),
                'formatted' => $this->currency->format(
                    round(
                        (($unit_price_special > 0) ? $unit_price_special : $unit_price_price) * $quantity,
                        2
                    ),
                    $this->session->data['currency']
                )
            ],
            // هل الأسعار تتضمن الضريبة؟
            'includes_tax' => true
        ],

        // بيانات تتعلق بالكمية
        'quantity_data' => [
            'minimum'   => $minimum_quantity,
            'maximum'   => $available_quantity,
            'current'   => $quantity,
            'available' => $available_quantity
        ],

        // لإعادة عرض خيارات المنتج بالواجهة
        'options'          => $product_options,
        'selected_values'  => $validatedSelectedValues,
        'options_data' => [
            'total_price'          => round($total_option_price, 2),
            'total_price_with_tax' => round($this->tax->calculate($total_option_price, $tax_class_id, true), 2),
            'selected_options'     => $selected_options
        ],

        // خصومات الكمية (مع current_quantity)
        'product_quantity_discounts' => $enhanced_discounts,

        // أقرب خصم قادم (دالة فرعية لاستخراج التخفيض التالي)
        'next_discount' => $this->getNextDiscount($enhanced_discounts, $quantity),

        // بيانات الباقات (للعرض)
        'bundles' => $this->getProductBundleswithoptionselected($product_id, $bundle_options),
        'bundle_data' => [
            'selected_bundles'            => $bundle_details,
            'total_bundle_price_no_tax'   => $bundle_items_no_tax_base,
            'total_bundle_price_with_tax' => round(
                $this->tax->calculate($bundle_items_no_tax_base, $tax_class_id, true),
                2
            )
        ],
        'success' => true
    ];
}



public function getProductBundleswithoptionselected($product_id, $bundle_options = array()) {
    $bundles = [];

    // 1) جلب جميع الباقات المرتبطة بالمنتج
    $bundle_query = $this->db->query("
        SELECT *
        FROM " . DB_PREFIX . "product_bundle
        WHERE product_id = '" . (int)$product_id . "'
          AND status = '1'
    ");

    $this->load->model('tool/image');

    // 2) المرور على كل باقة
    foreach ($bundle_query->rows as $bundle) {
        // جلب عناصر هذه الباقة
        $items_query = $this->db->query("
            SELECT pbi.*, p.image, p.tax_class_id, pd.name, p.product_id
            FROM " . DB_PREFIX . "product_bundle_item pbi
            LEFT JOIN " . DB_PREFIX . "product p
                   ON (pbi.product_id = p.product_id)
            LEFT JOIN " . DB_PREFIX . "product_description pd
                   ON (p.product_id = pd.product_id
                       AND pd.language_id = '" . (int)$this->config->get('config_language_id') . "')
            WHERE pbi.bundle_id = '" . (int)$bundle['bundle_id'] . "'
        ");

        // إن لم يوجد أي عنصر ضمن هذه الباقة، نتجاوزها
        if (!$items_query->num_rows) {
            continue;
        }

        // (أ) سنحسب قيمتين:
        // original_total_net = مجموع أسعار جميع المنتجات + الخيارات (حتى المجانية) قبل خصم الباقة.
        // final_total_net    = نفس المجموع لكن بعد تصفير المجاني.
        $original_total_net = 0.0;
        $final_total_net    = 0.0;

        $tax_class_id = 0; 
        $bundleItems  = [];

        // 3) المرور على كل منتج ضمن الباقة
        foreach ($items_query->rows as $item) {
            $productId = (int)$item['product_id'];
            $quantity  = (int)$item['quantity'];

            // نحتفظ بآخر ضريبة (لو أردت تعقيدًا أكبر إن اختلفت الضرائب، تحتاج منطق آخر)
            $tax_class_id = (int)$item['tax_class_id'];

            // 1) السعر الأساسي (صافي) للمنتج
            $base_price_net   = $this->getProductUnitPrice($productId, $item['unit_id']);
            // 2) السعر الخاص (صافي) إن وُجد وكان أقل من الأساسي
            $special_price_net= $this->getProductSpecialPrice($productId, $item['unit_id']);

            // نختار السعر النهائي قبل الخيارات
            $item_base_net = ($special_price_net > 0 && $special_price_net < $base_price_net)
                             ? $special_price_net
                             : $base_price_net;

            // 3) حساب خيارات هذا المنتج
            $sum_options_net = 0.0;
            $selected_opts_for_item = [];

            if (!empty($bundle_options[$bundle['bundle_id']][$productId])) {
                $these_options = $bundle_options[$bundle['bundle_id']][$productId];
                foreach ($these_options as $po_id => $po_val) {
                    $valsArr = is_array($po_val) ? $po_val : [$po_val];
                    foreach ($valsArr as $ov_id) {
                        $opt_info = $this->getOptionPrice($productId, $po_id, $ov_id);
                        if (!$opt_info) {
                            continue;
                        }

                        $opt_price = (float)$opt_info['price'];
                        $prefix    = $opt_info['price_prefix'];

                        if ($prefix === '=') {
                            // استبدال كامل للسعر
                            $item_base_net = $opt_price;
                            // لا نضيف بعده sum_options
                            $sum_options_net = 0;
                            // إن شئت: break 2;
                        } elseif ($prefix === '+') {
                            $sum_options_net += $opt_price;
                        } elseif ($prefix === '-') {
                            $sum_options_net -= $opt_price;
                        }

                        // تفاصيل الخيار للعرض
                        $detail = $this->getOptionDetailForBundle($productId, $po_id, $ov_id);
                        if ($detail) {
                            $selected_opts_for_item[] = [
                                'option_name'     => $detail['option_name'],
                                'option_value'    => $detail['option_value'],
                                'formatted_price' => $detail['formatted_price']
                            ];
                        } else {
                            // بديل أبسط
                            $selected_opts_for_item[] = [
                                'option_name'     => $opt_info['name'],
                                'option_value'    => "(OV#$ov_id)",
                                'formatted_price' => $prefix . $opt_price
                            ];
                        }
                    }
                }
            }

            // 4) السعر النهائي (صافي) بعد الخيارات لقطعة واحدة
            $item_final_net = $item_base_net + $sum_options_net;

            // 5) لو المنتج مجاني => نصفر القيمة
            $is_free         = !empty($item['is_free']);
            $original_line_net = $item_final_net * $quantity; // قبل التصفير
            $final_line_net    = $is_free ? 0.0 : $original_line_net;

            // نجمع لحساب المجموع العام
            $original_total_net += $original_line_net; 
            $final_total_net    += $final_line_net;

            // تجهيز الصورة
            $image = $this->model_tool_image->resize(
                ($item['image'] ?: 'placeholder.png'),
                100, 100
            );

            // نحسب ضريبة السطر (بعد التصفير لو مجاني)
            $line_total_with_tax = $this->tax->calculate($final_line_net, $tax_class_id, true);

            // تفاصيل العنصر
            $bundleItems[] = [
                'product_id'  => $productId,
                'name'        => $item['name'],
                'image'       => $image,
                'quantity'    => $quantity,
                'unit_id'     => $item['unit_id'],
                'unit_name'   => $this->getUnitName($item['unit_id']),
                'is_free'     => $is_free,

                // الأسعار التفصيلية:
                // - السعر الأساسي للمنتج قبل الخيارات
                'base_price_net'         => (float)$base_price_net,
                'base_price_formatted'   => $this->currency->format($base_price_net, $this->session->data['currency']),

                // - مجموع سعر الخيارات
                'options_price_net'      => (float)$sum_options_net,
                'options_price_formatted'=> $this->currency->format($sum_options_net, $this->session->data['currency']),

                // - السعر النهائي للقطعة (بعد options)
                'final_price_net'        => (float)$item_final_net,
                'final_price_formatted'  => $this->currency->format($item_final_net, $this->session->data['currency']),

                // - إجمالي السطر (صافي)
                'line_total_net'         => (float)$final_line_net,
                'line_total_formatted'   => $this->currency->format($final_line_net, $this->session->data['currency']),

                // - إجمالي السطر (مع الضريبة)
                'line_total_with_tax'    => (float)$line_total_with_tax,
                'line_total_with_tax_formatted' => $this->currency->format($line_total_with_tax, $this->session->data['currency']),

                // - تفاصيل الخيارات نفسها
                'selected_options'       => $selected_opts_for_item
            ];
        } // نهاية foreach(item)

        // 4) طبّق خصم الباقة (إن وجد)
        $bundle_discount_type  = $bundle['discount_type'];
        $bundle_discount_value = (float)$bundle['discount_value'];

        // original_net = بعد تصفير المجاني
        $original_net = $final_total_net;
        $final_net_afterDiscount = $original_net;

        if ($bundle_discount_type === 'percentage') {
            $discAmt = $original_net * ($bundle_discount_value / 100.0);
            $final_net_afterDiscount = max($original_net - $discAmt, 0);
        } elseif ($bundle_discount_type === 'fixed') {
            $discAmt = $bundle_discount_value;
            $final_net_afterDiscount = max($original_net - $discAmt, 0);
        } else {
            // type=product => لا خصم إضافي شامل؛ اكتفينا بعلامة المجاني
            $discAmt = 0.0;
        }

        // 5) احسب الضريبة قبل/بعد
        // لكن لاحظ أننا نريد أيضًا original_total_net (يشمل المنتجات المجانية قبل تصفيرها!) 
        // لعرض "بدلاً من..." للمشتري.
        // لذلك:
        $taxed_original = $this->tax->calculate($original_total_net, $tax_class_id, true);
        $taxed_final    = $this->tax->calculate($final_net_afterDiscount, $tax_class_id, true);

        $savings = $taxed_original - $taxed_final;
        if ($savings < 0) {
            $savings = 0;
        }
        $savings_percentage = 0;
        if ($taxed_original > 0 && $savings > 0) {
            $savings_percentage = round(($savings / $taxed_original) * 100);
        }

        // 6) بناء مصفوفة النتيجة
        $bundles[] = [
            'bundle_id'     => $bundle['bundle_id'],
            'name'          => $bundle['name'],
            'discount_type' => $bundle_discount_type,
            'discount_value'=> $bundle_discount_value,
            'items'         => $bundleItems,

            // السعر الأصلي = جميع المنتجات (بما فيها المجانية) قبل الخصم، مع الضريبة:
            'original_price'=> $this->currency->format($taxed_original, $this->session->data['currency']),

            // السعر النهائي بعد خصم الباقة (إن وجد)، مع الضريبة
            'total_price'   => $this->currency->format($taxed_final,   $this->session->data['currency']),

            'savings'       => ($savings > 0)
                                ? $this->currency->format($savings, $this->session->data['currency'])
                                : false,
            'savings_percentage' => $savings_percentage
        ];
    } // نهاية foreach باقة

    return $bundles;
}


/**
 * مساعد لاختيار الخصم التالي
 */
private function getNextDiscount($discounts, $quantity) {
    foreach ($discounts as $d) {
        if ($d['buy_quantity'] > $quantity) {
            return [
                'buy_quantity'   => $d['buy_quantity'],
                'discount_value' => isset($d['discount_value']) ? $d['discount_value'] : 0,
                'type'           => isset($d['type']) ? $d['type'] : ''
            ];
        }
    }
    return null;
}





/**
 * دالة لجلب تفاصيل الخيار (اسم الخيار + اسم القيمة + السعر بشكل منسق)
 * تُستخدم داخل منطق حساب الباقة عند حساب كل عنصر.
 *
 * @param int $product_id
 * @param int $product_option_id
 * @param int $product_option_value_id
 *
 * @return array|null  تُعيد [ 'option_name' => '...', 'option_value' => '...', 'formatted_price' => '+10.00' ] أو null
 */
private function getOptionDetailForBundle($product_id, $product_option_id, $product_option_value_id) {
    // استعلام: نجلب السعر / price_prefix / اسم الخيار / اسم القيمة
    $sql = "SELECT
                pov.price,
                pov.price_prefix,
                ovd.name AS value_name,
                od.name AS option_name
            FROM " . DB_PREFIX . "product_option_value pov
            LEFT JOIN " . DB_PREFIX . "product_option po 
                   ON (pov.product_option_id = po.product_option_id)
            LEFT JOIN " . DB_PREFIX . "option_description od
                   ON (po.option_id = od.option_id)
            LEFT JOIN " . DB_PREFIX . "option_value_description ovd
                   ON (pov.option_value_id = ovd.option_value_id)
            WHERE pov.product_id = '" . (int)$product_id . "'
              AND pov.product_option_id = '" . (int)$product_option_id . "'
              AND pov.product_option_value_id = '" . (int)$product_option_value_id . "'
              AND od.language_id = '" . (int)$this->config->get('config_language_id') . "'
              AND ovd.language_id = '" . (int)$this->config->get('config_language_id') . "'
            LIMIT 1";

    $query = $this->db->query($sql);
    if ($query->num_rows) {
        $price     = (float)$query->row['price'];
        $prefix    = $query->row['price_prefix'];
        $optName   = $query->row['option_name'];
        $valName   = $query->row['value_name'];

        // تنسيق للسعر مثلاً (+5.00)
        $formatted = '';
        if ($price != 0) {
            // إن أردت حساب ضريبة الخيار:
            $product_info = $this->getProduct($product_id);
            $taxed_price  = $this->tax->calculate($price, $product_info['tax_class_id'], true);

            // هنا نفترض مجرد تنسيق بسيط بدون ضريبة (أو تضيفه حسب رغبتك)
            $formatted_price = number_format($taxed_price, 2, '.', '');
            $formatted = $prefix . $this->currency->format($formatted_price, $this->session->data['currency']);
        }

        return array(
            'option_name'    => $optName,   // مثلا "اللون"
            'option_value'   => $valName,   // مثلا "أحمر"
            'formatted_price'=> $formatted  // مثلا "+3.00"
        );
    }
    return null;
}



public function getProductUnitPrice($product_id, $unit_id) {
    // البحث عن السعر المباشر للوحدة المطلوبة
    $pricing_query = $this->db->query("SELECT base_price FROM " . DB_PREFIX . "product_pricing WHERE product_id = '" . (int)$product_id . "' AND unit_id = '" . (int)$unit_id . "'");

    if ($pricing_query->num_rows) {
        return $pricing_query->row['base_price'];
    }

    // إذا لم يوجد سعر مباشر، نبحث عن معامل التحويل للوحدة
    $unit_query = $this->db->query("SELECT unit_type, conversion_factor FROM " . DB_PREFIX . "product_unit WHERE product_id = '" . (int)$product_id . "' AND unit_id = '" . (int)$unit_id . "'");

    if ($unit_query->num_rows) {
        // إذا كانت وحدة أساسية
        if ($unit_query->row['unit_type'] == 'base') {
            return $this->getBasePrice($product_id);
        }

        // إذا كانت وحدة إضافية مع معامل تحويل
        if ($unit_query->row['conversion_factor'] > 0) {
            $base_price = $this->getBasePrice($product_id);
            return $base_price * $unit_query->row['conversion_factor'];
        }
    }

    return 0;
}

public function getProductSpecialPrice($product_id, $unit_id) {
    $query = $this->db->query("SELECT special_price FROM " . DB_PREFIX . "product_pricing WHERE product_id = '" . (int)$product_id . "' AND unit_id = '" . (int)$unit_id . "'");

    // التحقق من وجود سعر خاص وأنه أقل من السعر الأساسي
    if ($query->num_rows) {
        $special_price = $query->row['special_price'];
        $base_price = $this->getProductUnitPrice($product_id, $unit_id);

        if ($special_price > 0 && $special_price < $base_price) {
            return $special_price;
        }
    }

    return 0;
}


   public function getProductUnitDetails($product_id, $unit_id) {
       return $this->db->query("
           SELECT pu.*, CONCAT(desc_en, ' - ', desc_ar) AS unit_name,u.code, u.desc_en, u.desc_ar
           FROM " . DB_PREFIX . "product_unit pu
           JOIN " . DB_PREFIX . "unit u ON (pu.unit_id = u.unit_id)
           WHERE pu.product_id = '" . (int)$product_id . "'
           AND pu.unit_id = '" . (int)$unit_id . "'
       ")->row;
   }



// دالة مساعدة للحصول على السعر الأساسي للمنتج
protected function getBasePrice($product_id) {
    $query = $this->db->query("SELECT pp.base_price 
        FROM " . DB_PREFIX . "product_pricing pp 
        INNER JOIN " . DB_PREFIX . "product_unit pu 
            ON pp.product_id = pu.product_id 
            AND pp.unit_id = pu.unit_id 
        WHERE pp.product_id = '" . (int)$product_id . "' 
        AND pu.unit_type = 'base' 
        LIMIT 1");

    return $query->num_rows ? $query->row['base_price'] : 0;
}

    // دالة لجلب خصومات الكمية للمنتج


public function getProductQuantityDiscounts($product_id,$current_quantity, $unit_id = null) {
    $today = date('Y-m-d');
    $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_quantity_discounts 
        WHERE product_id = '" . (int)$product_id . "' 
        AND status = 1 
        AND (unit_id='0' OR unit_id IS NULL OR unit_id = '" . (int)$unit_id . "')
        AND (date_start = '0000-00-00' OR date_start <= '" . $today . "')
        AND (date_end = '0000-00-00' OR date_end >= '" . $today . "')
        ORDER BY buy_quantity ASC");
    $discounts = [];
    foreach ($query->rows as $row) {
        $discounts[] = [
            'discount_id' => $row['discount_id'],
			'current_quantity' => $current_quantity,
            'name' => $row['name'],
            'type' => $row['type'],
            'buy_quantity' => (int)$row['buy_quantity'],
            'get_quantity' => (int)$row['get_quantity'],
            'discount_type' => $row['discount_type'],
            'discount_value' => (float)$row['discount_value'],
            'display_text' => $this->formatDiscountDisplayText($row)
        ];
    }

    return $discounts;
}

private function formatDiscountDisplayText($discount) {
    switch ($discount['type']) {
        case 'buy_x_get_y':
            return sprintf(
                "Buy %d, Get %d Free", 
                $discount['buy_quantity'], 
                $discount['get_quantity']
            );
        
        case 'buy_x_get_discount':
            if ($discount['discount_type'] == 'percentage') {
                return sprintf(
                    "Buy %d, Get %d%% Off", 
                    $discount['buy_quantity'], 
                    $discount['discount_value']
                );
            } else {
                return sprintf(
                    "Buy %d, Get %s Off", 
                    $discount['buy_quantity'], 
                    $this->currency->format($discount['discount_value'])
                );
            }
    }
}
    // دالة لجلب الباقات المرتبطة بالمنتج
public function getProductBundles($product_id) {
    $bundles = array();

    // جلب الباقات المفعّلة لهذا المنتج
    $query = $this->db->query("
        SELECT * FROM " . DB_PREFIX . "product_bundle 
        WHERE product_id = '" . (int)$product_id . "' 
        AND status = '1'
    ");

    $this->load->model('tool/image');

    foreach ($query->rows as $bundle) {
        // جلب عناصر الباقة
        $items_query = $this->db->query("
            SELECT pbi.*, p.image, pd.name, p.tax_class_id, p.product_id
            FROM " . DB_PREFIX . "product_bundle_item pbi 
            LEFT JOIN " . DB_PREFIX . "product p ON (pbi.product_id = p.product_id)
            LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id AND pd.language_id = '" . (int)$this->config->get('config_language_id') . "')
            WHERE pbi.bundle_id = '" . (int)$bundle['bundle_id'] . "'
        ");

        if (!$items_query->num_rows) {
            continue;
        }

        $original_total = 0.0;
        $items = array();
        $tax_class_id = 0;

        // حساب المجموع الأصلي بدون خصم (يشمل المنتجات المجانية أيضًا لظهور التوفير)
        foreach ($items_query->rows as $item) {
            $price = $this->getProductUnitPrice($item['product_id'], $item['unit_id']);
            $special = $this->getProductSpecialPrice($item['product_id'], $item['unit_id']);
            $item_base_price = ($special > 0 && $special < $price) ? $special : $price;

            $tax_class_id = $item['tax_class_id'];

            // نضيف كل المنتجات بما فيها المجانية للأصل
            $original_total += ($item_base_price * $item['quantity']);

            $image = $this->model_tool_image->resize(($item['image'] ?: 'placeholder.png'), 100, 100);

            $items[] = array(
                'product_id' => $item['product_id'],
                'name'       => $item['name'],
                'image'      => $image,
                'quantity'   => (int)$item['quantity'],
                'unit_id'    => $item['unit_id'],
                'unit_name'  => $this->getUnitName($item['unit_id']),
                'is_free'    => (bool)$item['is_free'],
                'base_price' => $item_base_price
            );
        }

        // حساب الخصم الإجمالي
        $discount_type = $bundle['discount_type'];
        $discount_value = (float)$bundle['discount_value'];

        $final_total = $original_total;
        if ($discount_type == 'percentage') {
            $discount_amount = $original_total * ($discount_value / 100.0);
            $final_total = max($original_total - $discount_amount, 0);
        } elseif ($discount_type == 'fixed') {
            $discount_amount = $discount_value;
            $final_total = max($original_total - $discount_amount, 0);
        } elseif ($discount_type == 'product') {
            // لا يوجد خصم إضافي، فقط منتجات مجانية
            $discount_amount = 0;
        } else {
            $discount_amount = 0;
        }

        // نسبة الخصم النسبي (للمنتجات المدفوعة)
        $discount_ratio = ($original_total > 0) ? (($original_total - $final_total) / $original_total) : 0;

        // إعادة حساب final_total بعدما نجعل المنتجات المجانية بسعر 0
        $recalculated_final = 0.0;

        foreach ($items as &$it) {
            $line_original = $it['base_price'] * $it['quantity'];
            if ($it['is_free']) {
                // المنتج مجاني: النهائي 0
                $line_final = 0;
            } else {
                // تطبيق الخصم النسبي على المنتجات غير المجانية
                $line_final = $line_original - ($line_original * $discount_ratio);
            }

            $recalculated_final += $line_final;

            // حساب السعر مع الضريبة لكل عنصر
            $taxed_item_original = $this->tax->calculate($line_original, $tax_class_id, true);
            $taxed_item_final = $this->tax->calculate($line_final, $tax_class_id, true);

            $it['original_price_formatted'] = $this->currency->format($taxed_item_original, $this->session->data['currency']);
            $it['final_price_formatted']    = $this->currency->format($taxed_item_final, $this->session->data['currency']);
        }
        unset($it);

        // final_total الجديد بعد تعديل المنتجات المجانية
        $final_total = $recalculated_final;

        // حساب التوفير مع الضريبة
        $taxed_original = $this->tax->calculate($original_total, $tax_class_id, true);
        $taxed_final = $this->tax->calculate($final_total, $tax_class_id, true);

        $savings = $taxed_original - $taxed_final;
        $savings_percentage = ($taxed_original > 0 && $savings > 0) ? round(($savings / $taxed_original) * 100) : 0;

        $bundles[] = array(
            'bundle_id' => $bundle['bundle_id'],
            'name'      => $bundle['name'],
            'description' => isset($bundle['description']) ? $bundle['description'] : '',
            'discount_type' => $bundle['discount_type'],
            'discount_value' => $bundle['discount_value'],
            'items' => $items,
            'original_price' => $this->currency->format($taxed_original, $this->session->data['currency']),
            'total_price'    => $this->currency->format($taxed_final, $this->session->data['currency']),
            'savings'        => $savings > 0 ? $this->currency->format($savings, $this->session->data['currency']) : false,
            'savings_percentage' => $savings_percentage
        );
    }

    return $bundles;
}


    public function getProductBundle($bundle_id) {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_bundle WHERE bundle_id = '" . (int)$bundle_id . "'");
        return $query->row;
    }

public function getBundleOptions() {
    $this->load->language('product/product');
    $this->load->model('catalog/product');

    $json = array();

    if (isset($this->request->get['bundle_id'])) {
        $bundle_id = (int)$this->request->get['bundle_id'];
        $bundle_products = $this->model_catalog_product->getBundleProducts($bundle_id);

        if ($bundle_products) {
            $json['success'] = true;
            $json['bundle_products'] = array();

            foreach ($bundle_products as $product) {
                $options = $this->model_catalog_product->getProductOptions($product['product_id']);
                $json['bundle_products'][] = array(
                    'product_id' => $product['product_id'],
                    'name' => $product['name'],
                    'options' => $options
                );
            }
        } else {
            $json['error'] = $this->language->get('error_invalid_bundle');
        }
    } else {
        $json['error'] = $this->language->get('error_missing_bundle_id');
    }

    $this->response->addHeader('Content-Type: application/json');
    $this->response->setOutput(json_encode($json));
}


public function getBundleItems($bundle_id) {
    $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_bundle_item WHERE bundle_id = '" . (int)$bundle_id . "'");
    return $query->rows;
}

    public function getBundleItem($bundle_id, $product_id) {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_bundle_item WHERE bundle_id = '" . (int)$bundle_id . "' AND product_id = '" . (int)$product_id . "'");
        return $query->row;
    }


    public function getProductOption($product_id, $option_id) {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_option po LEFT JOIN `" . DB_PREFIX . "option` o ON (po.option_id = o.option_id) LEFT JOIN " . DB_PREFIX . "option_description od ON (o.option_id = od.option_id) WHERE po.product_id = '" . (int)$product_id . "' AND po.option_id = '" . (int)$option_id . "' AND od.language_id = '" . (int)$this->config->get('config_language_id') . "'");
        return $query->row;
    }

    public function getProductOptionValue($product_id, $option_id, $option_value_id) {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_option_value pov LEFT JOIN " . DB_PREFIX . "option_value ov ON (pov.option_value_id = ov.option_value_id) LEFT JOIN " . DB_PREFIX . "option_value_description ovd ON (ov.option_value_id = ovd.option_value_id) WHERE pov.product_id = '" . (int)$product_id . "' AND pov.option_id = '" . (int)$option_id . "' AND pov.option_value_id = '" . (int)$option_value_id . "' AND ovd.language_id = '" . (int)$this->config->get('config_language_id') . "'");
        return $query->row;
    }
    
    

	public function updateViewed($product_id) {
		$this->db->query("UPDATE " . DB_PREFIX . "product SET viewed = (viewed + 1) WHERE product_id = '" . (int)$product_id . "'");
	}


public function getProduct($product_id) {

    // Original query
    $query = $this->db->query("SELECT DISTINCT *, pd.name AS name, p.image, m.name AS manufacturer, (SELECT points FROM " . DB_PREFIX . "product_reward pr WHERE pr.product_id = p.product_id AND pr.customer_group_id = '" . (int)$this->config->get('config_customer_group_id') . "') AS reward, (SELECT ss.name FROM " . DB_PREFIX . "stock_status ss WHERE ss.stock_status_id = p.stock_status_id AND ss.language_id = '" . (int)$this->config->get('config_language_id') . "') AS stock_status, (SELECT wcd.unit FROM " . DB_PREFIX . "weight_class_description wcd WHERE p.weight_class_id = wcd.weight_class_id AND wcd.language_id = '" . (int)$this->config->get('config_language_id') . "') AS weight_class, (SELECT lcd.unit FROM " . DB_PREFIX . "length_class_description lcd WHERE p.length_class_id = lcd.length_class_id AND lcd.language_id = '" . (int)$this->config->get('config_language_id') . "') AS length_class, (SELECT AVG(rating) AS total FROM " . DB_PREFIX . "review r1 WHERE r1.product_id = p.product_id AND r1.status = '1' GROUP BY r1.product_id) AS rating, (SELECT COUNT(*) AS total FROM " . DB_PREFIX . "review r2 WHERE r2.product_id = p.product_id AND r2.status = '1' GROUP BY r2.product_id) AS reviews, p.sort_order FROM " . DB_PREFIX . "product p LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id) LEFT JOIN " . DB_PREFIX . "product_to_store p2s ON (p.product_id = p2s.product_id) LEFT JOIN " . DB_PREFIX . "manufacturer m ON (p.manufacturer_id = m.manufacturer_id) WHERE p.product_id = '" . (int)$product_id . "' AND pd.language_id = '" . (int)$this->config->get('config_language_id') . "' AND p.status = '1' AND p.date_available <= NOW() ");

    if ($query->num_rows) {
        $product_data = $query->row;
        
        try {
            // Get product units - with error handling
            $units = $this->getProductUnits($query->row['product_id']);
            $product_data['units'] = $units;
            
            // Log units for debugging
            error_log('Product ' . $product_id . ' units: ' . print_r($units, true));
            
            // Get product inventory - with error handling  
            try {
                $product_data['inventory'] = $this->getProductInventory($query->row['product_id']);
            } catch (Exception $e) {
                error_log('Error getting inventory for product ' . $product_id . ': ' . $e->getMessage());
                $product_data['inventory'] = array();
            }
            
            $default_unit_id = 37;
            if (!empty($units)) {
                $default_unit = $this->getDefaultUnit($units);
                if (isset($default_unit['unit_id'])) {
                    $default_unit_id = $default_unit['unit_id'];
                    $product_data['default_unit'] = $default_unit;
                } else {
                    // No default unit found, use a fallback
                    $default_unit_id = $units[0]['unit_id'] ?? 37;
                    $product_data['default_unit'] = $units[0] ?? array();
                }
            } else {
                $default_unit_id = 37;
                $product_data['default_unit'] = array(
                    'unit_id' => $default_unit_id,
                    'unit_type' => 'base',
                    'conversion_factor' => 1,
                    'unit_name' => 'Default'
                );
            }
            
            // Get product pricing - with error handling
            try {
                $product_data['pricing'] = $this->getProductPricing($query->row['product_id'], $default_unit_id);
            } catch (Exception $e) {
                error_log('Error getting pricing for product ' . $product_id . ': ' . $e->getMessage());
                $product_data['pricing'] = array();
            }
            
            // Get bundles - with error handling
            try {
                $product_data['bundles'] = $this->getProductBundles($product_id);
            } catch (Exception $e) {
                error_log('Error getting bundles for product ' . $product_id . ': ' . $e->getMessage());
                $product_data['bundles'] = array();
            }
            
            // Get quantity discounts - with error handling
            try {
                $product_data['product_quantity_discounts'] = $this->getProductQuantityDiscounts($product_id, 1, $default_unit_id);
            } catch (Exception $e) {
                error_log('Error getting quantity discounts for product ' . $product_id . ': ' . $e->getMessage());
                $product_data['product_quantity_discounts'] = array();
            }
        } catch (Exception $e) {
            error_log('Error in getProduct for ID ' . $product_id . ': ' . $e->getMessage());
            // Still return basic product data, even if extended data failed
        }
        
        return $product_data;
    } else {
        error_log('No product found with ID: ' . $product_id);
        return false;
    }
}
    
public function getCategoriesProducts($limit = 20, $categoryIds = []) {
    $product_data = [];

    foreach ($categoryIds as $categoryId) {
        $sql = "SELECT p.`product_id`
            FROM `" . DB_PREFIX . "product` p
            LEFT JOIN `" . DB_PREFIX . "product_description` pd ON (p.`product_id` = pd.`product_id`)
            LEFT JOIN `" . DB_PREFIX . "product_to_store` p2s ON (p.`product_id` = p2s.`product_id`)
            LEFT JOIN `" . DB_PREFIX . "product_to_category` p2c ON (p.`product_id` = p2c.`product_id`)
            WHERE pd.`language_id` = '" . (int)$this->config->get('config_language_id') . "'
            AND p.`status` = '1'
            AND p.`date_available` <= NOW()
            AND p2c.`category_id` = '" . (int)$categoryId . "'
            GROUP BY p.product_id
            ORDER BY RAND()
            LIMIT " . (int)$limit;

        $query = $this->db->query($sql);

        foreach ($query->rows as $result) {
            $product_data[$result['product_id']] = $this->model_catalog_product->getProduct($result['product_id']);
        }
    }

    return $product_data;
}
public function getBrandsProducts($limit=20, $manufacturerIds = []) {
    $product_data = [];
    $productsId = [];
    foreach ($manufacturerIds as $mid) {
    $sql = "SELECT p.`product_id`
        FROM `" . DB_PREFIX . "product` p
        LEFT JOIN `" . DB_PREFIX . "product_description` pd ON (p.`product_id` = pd.`product_id`)
        LEFT JOIN `" . DB_PREFIX . "product_to_store` p2s ON (p.`product_id` = p2s.`product_id`)
        WHERE pd.`language_id` = '" . (int)$this->config->get('config_language_id') . "'
        AND p.`status` = '1'
        AND p.`date_available` <= NOW()
        AND p.`manufacturer_id` = '".$mid."'
        GROUP BY p.product_id
        ORDER BY RAND()
        LIMIT " . (int)$limit;

        $query = $this->db->query($sql);

        foreach ($query->rows as $result) {
            $productsId[] = $result['product_id'];
        }
    }

    $productsId = array_unique($productsId);

    foreach ($productsId as $productId) {
        $product_data[$productId] = $this->model_catalog_product->getProduct($productId);
    }
    

    return $product_data;
}


    
    public function getProductCategories($product_id) {
        $query = $this->db->query("SELECT category_id FROM " . DB_PREFIX . "product_to_category WHERE product_id = '" . (int)$product_id . "'");
    
        $category_data = array();
    
        foreach ($query->rows as $result) {
            $category_info = $this->getCategory($result['category_id']);
    
            if ($category_info) {
                $category_data[] = array(
                    'category_id' => $category_info['category_id'],
                    'name'        => $category_info['name']
                );
            }
        }
    
        return $category_data;
    }
    
    protected function getCategory($category_id) {
        $query = $this->db->query("SELECT DISTINCT * FROM " . DB_PREFIX . "category c LEFT JOIN " . DB_PREFIX . "category_description cd ON (c.category_id = cd.category_id) LEFT JOIN " . DB_PREFIX . "category_to_store c2s ON (c.category_id = c2s.category_id) WHERE c.category_id = '" . (int)$category_id . "' AND cd.language_id = '" . (int)$this->config->get('config_language_id') . "' AND c.status = '1'");
    
        return $query->row;
    }

public function getMostViewsProducts($limit): array {
    $product_data = [];

    $query = $this->db->query("SELECT p.`product_id`, COUNT(*) AS total_views
        FROM `" . DB_PREFIX . "product` p
        LEFT JOIN `" . DB_PREFIX . "product_to_store` p2s ON (p.`product_id` = p2s.`product_id`)
        LEFT JOIN `" . DB_PREFIX . "product_viewed` pv ON (p.`product_id` = pv.`product_id`)
        WHERE p.`status` = '1'
        AND p.`date_available` <= NOW()
         
        GROUP BY p.`product_id`
        ORDER BY total_views DESC
        LIMIT " . (int)$limit);

    foreach ($query->rows as $result) {
        $product_data[$result['product_id']] = $this->getProduct($result['product_id']);
    }

    return $product_data;
}
public function getTagsProducts($limit, $tags = []) {
    $product_data = [];
    $productsId = [];

//    $tags = array_map([$this->db, 'escape'], $tags); // Escape tags to prevent SQL injection

//    $language_id = (int)$this->config->get('config_language_id');

    foreach ($tags as $tag) {
        $sql = "SELECT pd.`product_id`
            FROM `" . DB_PREFIX . "product_description` pd
            LEFT JOIN `" . DB_PREFIX . "product` p ON (pd.`product_id` = p.`product_id`)
            LEFT JOIN `" . DB_PREFIX . "product_to_store` p2s ON (p.`product_id` = p2s.`product_id`)
            WHERE p.`status` = '1'
            AND p.`date_available` <= NOW()
             
            AND pd.`tag` LIKE '%," . $tag . ",%'";

        $query = $this->db->query($sql);

        foreach ($query->rows as $result) {
            $productsId[] = $result['product_id'];
        }
    }

    $productsId = array_unique($productsId); // Remove duplicated product IDs

    foreach ($productsId as $productId) {
        $product_data[$productId] = $this->getProduct($productId);
    }

    return $product_data;
}
public function getFiltersProducts($limit, $filterIds = []) {
    $product_data = [];
    $productsId = [];

    $filterIds = array_map('intval', $filterIds); // Ensure filter IDs are integers

    $filterIds = implode(',', $filterIds); // Convert array to comma-separated string

    $sql = "SELECT pf.`product_id`
        FROM `" . DB_PREFIX . "product_filter` pf
        LEFT JOIN `" . DB_PREFIX . "product` p ON (pf.`product_id` = p.`product_id`)
        LEFT JOIN `" . DB_PREFIX . "product_to_store` p2s ON (p.`product_id` = p2s.`product_id`)
        WHERE p.`status` = '1'
        AND p.`date_available` <= NOW()
         
        AND pf.`filter_id` IN (" . $filterIds . ")";

    $query = $this->db->query($sql);

    foreach ($query->rows as $result) {
        $productsId[] = $result['product_id'];
    }

    $productsId = array_unique($productsId); // Remove duplicated product IDs

    foreach ($productsId as $productId) {
        $product_data[$productId] = $this->getProduct($productId);
    }

    return $product_data;
}
public function getOptionsProducts($limit, $optionValueIds = []) {
    $product_data = [];
    $productsId = [];

    $optionValueIds = array_map('intval', $optionValueIds); // Ensure option value IDs are integers

    $optionValueIds = implode(',', $optionValueIds); // Convert array to comma-separated string

    $sql = "SELECT po.`product_id`
        FROM `" . DB_PREFIX . "product_option_value` po
        LEFT JOIN `" . DB_PREFIX . "product` p ON (po.`product_id` = p.`product_id`)
        LEFT JOIN `" . DB_PREFIX . "product_to_store` p2s ON (p.`product_id` = p2s.`product_id`)
        WHERE p.`status` = '1'
        AND p.`date_available` <= NOW()
         
        AND po.`option_value_id` IN (" . $optionValueIds . ")";

    $query = $this->db->query($sql);

    foreach ($query->rows as $result) {
        $productsId[] = $result['product_id'];
    }

    $productsId = array_unique($productsId); // Remove duplicated product IDs

    foreach ($productsId as $productId) {
        $product_data[$productId] = $this->getProduct($productId);
    }

    return $product_data;
}

	
	public function getRandom(int $limit): array {

			$query = $this->db->query("SELECT DISTINCT p.`product_id` FROM `" . DB_PREFIX . "product` p LEFT JOIN `" . DB_PREFIX . "product_to_store` p2s ON (p.`product_id` = p2s.`product_id`) WHERE p.`status` = '1' AND p.`date_available` <= NOW()   ORDER BY rand() DESC LIMIT " . (int)$limit);

			foreach ($query->rows as $result) {
    				$product_data[$result['product_id']] = $this->getProduct($result['product_id']);
                
			}
		return (array)$product_data;
	}

    
public function getProductAvailableQuantity($product_id, $unit_id) {
    // استعلام لجلب الكمية المتاحة من المخزون للمنتج بناءً على الوحدة
    $query = $this->db->query("SELECT quantity_available FROM " . DB_PREFIX . "product_inventory WHERE product_id = '" . (int)$product_id . "' AND unit_id = '" . (int)$unit_id . "'");

    if ($query->num_rows) {
        return (float)$query->row['quantity_available'];
    } else {
        // في حالة عدم وجود سجل للوحدة المحددة، يمكن أن نرجع 0 كمخزون متاح
        return 0;
    }
}



public function getProductPricing($product_id, $unit_id) {
    $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_pricing WHERE product_id = '" . (int)$product_id . "' AND unit_id = '" . (int)$unit_id . "'");
    return $query->row;
}


public function getUnitName($unit_id) {
    $query = $this->db->query("SELECT CONCAT(desc_en, ' - ', desc_ar) AS unit_name FROM " . DB_PREFIX . "unit WHERE unit_id = '" . (int)$unit_id . "'");
    return $query->row ? $query->row['unit_name'] : '';
}

public function getUnit($unit_id) {
    $query = $this->db->query("SELECT CONCAT(desc_en, ' - ', desc_ar) AS unit_name,code,unit_id FROM " . DB_PREFIX . "unit WHERE unit_id = '" . (int)$unit_id . "'");
    return $query->row;
}

public function getProductPrices($product_id) {
    $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_pricing WHERE product_id = '" . (int)$product_id . "'");
    $prices = array();
    foreach ($query->rows as $row) {
        $prices[$row['unit_id']] = array(
            'retail' => $row['special_price']?$row['special_price']:$row['base_price'],
            'base_price' => $row['base_price'],
            'special_price' => $row['special_price']//,
            //'wholesale' => $row['wholesale_price'],
            //'half_wholesale' => $row['half_wholesale_price'],
            //'custom' => $row['custom_price']
        );
    }
    return $prices;
}

    public function getProductUnits($product_id) {
        $query = $this->db->query("SELECT pu.*, CONCAT(u.desc_en, ' - ', u.desc_ar) AS unit_name 
            FROM " . DB_PREFIX . "product_unit pu 
            LEFT JOIN " . DB_PREFIX . "unit u ON (pu.unit_id = u.unit_id) 
            WHERE pu.product_id = '" . (int)$product_id . "'");

        return $query->rows;
    }    
    

 
    public function updateQuantity($product_id, $quantity, $unit_id, $branch_id) {
        $this->db->query("UPDATE " . DB_PREFIX . "product_inventory 
                          SET quantity = quantity - " . (int)$quantity . " 
                          WHERE product_id = '" . (int)$product_id . "' 
                          AND unit_id = '" . (int)$unit_id . "' 
                          AND branch_id = '" . (int)$branch_id . "'");
    }

    public function checkAvailability($product_id, $quantity, $unit_id) {
        $query = $this->db->query("SELECT SUM(quantity_available) AS total_quantity 
                                   FROM " . DB_PREFIX . "product_inventory
                                   WHERE product_id = '" . (int)$product_id . "'
                                   AND unit_id = '" . (int)$unit_id . "'");

        $total_quantity = $query->row['total_quantity'];

        return $total_quantity >= $quantity;
    }   
/*
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
            'special_price' => $row['special_price']//,
            //'wholesale_price' => $row['wholesale_price'],
            //'half_wholesale_price' => $row['half_wholesale_price'],
            //'custom_price' => $row['custom_price']
        );
    }
    
    return $pricing;
}
*/
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





public function getProductRecommendations($product_id) {
    $recommendations = array();
    $customer_group_id = (int)$this->config->get('config_customer_group_id');
    
    $recommendation_query = $this->db->query("SELECT pr.*, p.image, pd.name, pr.type, pr.priority, pr.unit_id
        FROM " . DB_PREFIX . "product_recommendation pr
        LEFT JOIN " . DB_PREFIX . "product p ON (pr.related_product_id = p.product_id)
        LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id)
        WHERE pr.product_id = '" . (int)$product_id . "' 
        AND (pr.customer_group_id = '0' OR pr.customer_group_id = '" . $customer_group_id . "')
        AND pd.language_id = '" . (int)$this->config->get('config_language_id') . "'
        AND p.status = '1' AND p.date_available <= NOW()
        ORDER BY pr.priority DESC");

    if ($recommendation_query->num_rows) {
        foreach ($recommendation_query->rows as $recommendation) {
            $units = $this->getProductUnits($recommendation['related_product_id']);
            $default_unit = $this->getDefaultUnit($units);
            
            // Use the specified unit if available, otherwise use the default unit
            $unit_id = $recommendation['unit_id'] ? $recommendation['unit_id'] : $default_unit['unit_id'];
            
            // Get available quantity for the selected unit
            $available_quantity = $this->getAvailableQuantityForOnline($recommendation['related_product_id'], $unit_id);
                $this->load->model('tool/image');
                if ($recommendation['image'] && is_file(DIR_IMAGE . $recommendation['image'])) {
                    $image = $this->model_tool_image->resize($recommendation['image'], 100, 100);
                } else {
                    $image = $this->model_tool_image->resize('placeholder.png', 100, 100);
                }
                if ($available_quantity > 0) {
                $recommendations[] = array(
                    'recommendation_id' => $recommendation['recommendation_id'],
                    'product_id' => $recommendation['related_product_id'],
                    'name' => $recommendation['name'],
                    'image' => $image,
                    'thumb' => $image,
                    'type' => $recommendation['type'],
                    'discount_type' => $recommendation['discount_type'],
                    'discount_value' => $recommendation['discount_value'],
                    'units' => $units,
                    'default_unit' => $default_unit,
                    'selected_unit_id' => $unit_id,
                    'available_quantity' => $available_quantity,
                    'priority' => $recommendation['priority']
                );
            }
        }
    }
    
    // Separate recommendations by type
    $upsell_recommendations = array_filter($recommendations, function($item) {
        return $item['type'] == 'upsell';
    });
    
    $cross_sell_recommendations = array_filter($recommendations, function($item) {
        return $item['type'] == 'cross_sell';
    });
    
    return array(
        'upsell' => $upsell_recommendations,
        'cross_sell' => $cross_sell_recommendations
    );
}





public function getProductSpecials($product_id) {
    $query = $this->db->query("SELECT pp.unit_id, pp.special_price as price, CONCAT(u.desc_en, ' - ', u.desc_ar) AS unit_name
                               FROM " . DB_PREFIX . "product_pricing pp
                               LEFT JOIN " . DB_PREFIX . "unit u ON (pp.unit_id = u.unit_id)
                               WHERE pp.product_id = '" . (int)$product_id . "' 
                               AND pp.special_price IS NOT NULL 
                               AND pp.special_price > 0");
    return $query->rows;
}


public function getProducts($data = array()) {
    $sql = "SELECT DISTINCT p.*, pd.name, egs.*,
                   COALESCE((SELECT SUM(pi.quantity) FROM " . DB_PREFIX . "product_inventory pi WHERE pi.product_id = p.product_id), 0) as quantity,
                   COALESCE((SELECT pp.base_price FROM " . DB_PREFIX . "product_pricing pp
                    WHERE pp.product_id = p.product_id AND pp.unit_id =
                        (SELECT pu.unit_id FROM " . DB_PREFIX . "product_unit pu
                         WHERE pu.product_id = p.product_id AND pu.unit_type = 'base' LIMIT 1)
                    LIMIT 1), 0.0) as price
            FROM " . DB_PREFIX . "product p
            LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id)
            LEFT JOIN " . DB_PREFIX . "product_egs egs ON (p.product_id = egs.product_id)
            LEFT JOIN " . DB_PREFIX . "product_unit pu ON (p.product_id = pu.product_id)
            LEFT JOIN " . DB_PREFIX . "product_pricing pp ON (p.product_id = pp.product_id AND pu.unit_id = pp.unit_id)";

    if (!empty($data['filter_category_id'])) {
        $sql .= " LEFT JOIN " . DB_PREFIX . "product_to_category p2c ON (p.product_id = p2c.product_id)";
    }

    if (!empty($data['filter_filter'])) {
        $sql .= " LEFT JOIN " . DB_PREFIX . "product_filter pf ON (p.product_id = pf.product_id)";
    }

    if (!empty($data['filter_option'])) {
        $sql .= " LEFT JOIN " . DB_PREFIX . "product_option_value pov ON (p.product_id = pov.product_id)";
    }


    $sql .= " WHERE pd.language_id = '" . (int)$this->config->get('config_language_id') . "' AND p.status = '1' AND p.date_available <= NOW()";

    if (!empty($data['filter_category_id'])) {
        $sql .= " AND p2c.category_id = '" . (int)$data['filter_category_id'] . "'";
    }

    if (!empty($data['filter_filter'])) {
        $implode = array();
        $filters = explode(',', $data['filter_filter']);
        foreach ($filters as $filter_id) {
            $implode[] = (int)$filter_id;
        }
        $sql .= " AND pf.filter_id IN (" . implode(',', $implode) . ")";
    }

    if (!empty($data['filter_name'])) {
        $sql .= " AND LCASE(pd.name) LIKE '%" . $this->db->escape(utf8_strtolower($data['filter_name'])) . "%'";
    }

    if (!empty($data['filter_model'])) {
        $sql .= " AND LCASE(p.model) LIKE '%" . $this->db->escape(utf8_strtolower($data['filter_model'])) . "%'";
    }
    
    //السعر قد يكون سعر عرض حال تفعيله وليس سعر اساسي فقط لان سعر العرض هو سعر البيع ولكم في حالة عدم وجوده سيكون السعر الاساسي
    if (!empty($data['filter_unit'])) {
        $sql .= " AND pu.unit_id = '" . (int)$data['filter_unit'] . "'";
    }

    if (!empty($data['filter_price_min']) && !empty($data['filter_price_max'])) {
        $sql .= " AND ((pp.special_price > 0 AND pp.special_price BETWEEN '" . (float)$data['filter_price_min'] . "' AND '" . (float)$data['filter_price_max'] . "') ";
        $sql .= " OR (pp.special_price = 0 AND pp.base_price BETWEEN '" . (float)$data['filter_price_min'] . "' AND '" . (float)$data['filter_price_max'] . "'))";
    } else if (!empty($data['filter_price_min'])) {
        $sql .= " AND ((pp.special_price > 0 AND pp.special_price >= '" . (float)$data['filter_price_min'] . "') ";
        $sql .= " OR (pp.special_price = 0 AND pp.base_price >= '" . (float)$data['filter_price_min'] . "'))";
    } else if (!empty($data['filter_price_max'])) {
        $sql .= " AND ((pp.special_price > 0 AND pp.special_price <= '" . (float)$data['filter_price_max'] . "') ";
        $sql .= " OR (pp.special_price = 0 AND pp.base_price <= '" . (float)$data['filter_price_max'] . "'))";
    }

    if (!empty($data['filter_quantity'])) {
        $sql .= " AND quantity >= '" . (int)$data['filter_quantity'] . "'";
    }


    if (!empty($data['filter_option'])) {
        $implode = array();
        $options = explode(',', $data['filter_option']);
        foreach ($options as $option_id) {
            $implode[] = (int)$option_id;
        }
        $sql .= " AND pov.option_id IN (" . implode(',', $implode) . ")";
    }

    $sort_data = array(
        'pd.name',
        'p.model',
        'price',
        'quantity',
        'p.status',
        'p.sort_order'
    );

    if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
        $sql .= " ORDER BY " . $data['sort'];
    } else {
        $sql .= " ORDER BY pd.name";
    }

    if (isset($data['order']) && ($data['order'] == 'DESC')) {
        $sql .= " DESC";
    } else {
        $sql .= " ASC";
    }

    if (isset($data['start']) || isset($data['limit'])) {
        if ($data['start'] < 0) {
            $data['start'] = 0;
        }

        if ($data['limit'] < 1) {
            $data['limit'] = 20;
        }

        $sql .= " LIMIT " . (int)$data['start'] . "," . (int)$data['limit'];
    }
    


    $query = $this->db->query($sql);
    return $query->rows;
}

public function getProductPriceRange($data = array()) {
   $sql = "SELECT 
                MIN(
                    CASE 
                        WHEN pu.unit_type = 'base' THEN LEAST(pp.base_price, IFNULL(pp.special_price, pp.base_price))
                        ELSE LEAST(pp.base_price / pu.conversion_factor, IFNULL(pp.special_price, pp.base_price) / pu.conversion_factor)
                    END
                ) AS min_price,
                MAX(
                    CASE 
                        WHEN pu.unit_type = 'base' THEN GREATEST(pp.base_price, IFNULL(pp.special_price, pp.base_price))
                        ELSE GREATEST(pp.base_price / pu.conversion_factor, IFNULL(pp.special_price, pp.base_price) / pu.conversion_factor)
                    END
                ) AS max_price
            FROM " . DB_PREFIX . "product_pricing pp
            LEFT JOIN " . DB_PREFIX . "product p ON (pp.product_id = p.product_id)
            LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id)
            LEFT JOIN " . DB_PREFIX . "product_to_store p2s ON (p.product_id = p2s.product_id)
            LEFT JOIN " . DB_PREFIX . "product_unit pu ON (pp.product_id = pu.product_id AND pp.unit_id = pu.unit_id)
            LEFT JOIN " . DB_PREFIX . "unit u ON (pu.unit_id = u.unit_id)";

    if (!empty($data['filter_category_id'])) {
        $sql .= " LEFT JOIN " . DB_PREFIX . "product_to_category p2c ON (p.product_id = p2c.product_id)";
    }

    if (!empty($data['filter_filter'])) {
        $sql .= " LEFT JOIN " . DB_PREFIX . "product_filter pf ON (p.product_id = pf.product_id)";
    }

    $sql .= " WHERE pd.language_id = '" . (int)$this->config->get('config_language_id') . "'
              AND p.status = '1'
              AND p.date_available <= NOW()
              ";

    if (!empty($data['filter_category_id'])) {
        $sql .= " AND p2c.category_id = '" . (int)$data['filter_category_id'] . "'";
    }

    if (!empty($data['filter_filter'])) {
        $implode = array();
        $filters = explode(',', $data['filter_filter']);
        foreach ($filters as $filter_id) {
            $implode[] = (int)$filter_id;
        }
        $sql .= " AND pf.filter_id IN (" . implode(',', $implode) . ")";
    }

    if (!empty($data['filter_name']) || !empty($data['filter_tag'])) {
        $sql .= " AND (";

        if (!empty($data['filter_name'])) {
            $implode = array();
            $words = explode(' ', trim(preg_replace('/\s+/', ' ', $data['filter_name'])));
            foreach ($words as $word) {
                $implode[] = "pd.name LIKE '%" . $this->db->escape($word) . "%'";
            }
            if ($implode) {
                $sql .= " " . implode(" AND ", $implode) . "";
            }
            if (!empty($data['filter_description'])) {
                $sql .= " OR pd.description LIKE '%" . $this->db->escape($data['filter_name']) . "%'";
            }
        }

        if (!empty($data['filter_name']) && !empty($data['filter_tag'])) {
            $sql .= " OR ";
        }

        if (!empty($data['filter_tag'])) {
            $implode = array();
            $words = explode(' ', trim(preg_replace('/\s+/', ' ', $data['filter_tag'])));
            foreach ($words as $word) {
                $implode[] = "pd.tag LIKE '%" . $this->db->escape($word) . "%'";
            }
            if ($implode) {
                $sql .= " " . implode(" AND ", $implode) . "";
            }
        }

        $sql .= ")";
    }

    if (!empty($data['filter_manufacturer_id'])) {
        $sql .= " AND p.manufacturer_id = '" . (int)$data['filter_manufacturer_id'] . "'";
    }

    if (!empty($data['filter_unit'])) {
        $sql .= " AND pp.unit_id = '" . (int)$data['filter_unit'] . "'";
    }

    $query = $this->db->query($sql);

    if ($query->num_rows) {
        return array(
            'min_price' => $query->row['min_price'],
            'max_price' => $query->row['max_price']
        );
    } else {
        return array(
            'min_price' => 0,
            'max_price' => 0
        );
    }
}

//وحدات المنتجات بالقسم مع احترام الفلاتر
public function getProductsUnits($product_ids = array(), $filter_data = array()) {
    $units = array();

    if (empty($product_ids)) {
        return $units;
    }

$sql = "SELECT pu.product_id, pu.unit_id, CONCAT(u.desc_en, ' - ', u.desc_ar) AS unit_name "
     . " FROM " . DB_PREFIX . "product_unit pu"
     . " LEFT JOIN " . DB_PREFIX . "unit u ON (pu.unit_id = u.unit_id)"
     . " WHERE pu.product_id IN (" . implode(',', array_map('intval', $product_ids)) . ")";

    // تطبيق الفلاتر المتعلقة بالمنتجات
    if (!empty($filter_data['filter_category_id'])) {
        $sql .= " AND pu.product_id IN (SELECT p2c.product_id FROM " . DB_PREFIX . "product_to_category p2c WHERE p2c.category_id = '" . (int)$filter_data['filter_category_id'] . "')";
    }

    if (!empty($filter_data['filter_filter'])) {
        $implode = array();
        $filters = explode(',', $filter_data['filter_filter']);
        foreach ($filters as $filter_id) {
            $implode[] = (int)$filter_id;
        }
        $sql .= " AND pu.product_id IN (SELECT pf.product_id FROM " . DB_PREFIX . "product_filter pf WHERE pf.filter_id IN (" . implode(',', $implode) . "))";
    }

    if (!empty($filter_data['filter_name'])) {
        $sql .= " AND pu.product_id IN (SELECT pd.product_id FROM " . DB_PREFIX . "product_description pd WHERE LCASE(pd.name) LIKE '%" . $this->db->escape(utf8_strtolower($filter_data['filter_name'])) . "%')";
    }

    if (!empty($filter_data['filter_model'])) {
        $sql .= " AND pu.product_id IN (SELECT p.product_id FROM " . DB_PREFIX . "product p WHERE LCASE(p.model) LIKE '%" . $this->db->escape(utf8_strtolower($filter_data['filter_model'])) . "%')";
    }

    if (!empty($filter_data['unit_type'])) {
        $sql .= " AND u.unit_type = '" . $this->db->escape($filter_data['unit_type']) . "'";
    }

    if (!empty($filter_data['unit_id'])) {
        $sql .= " AND pu.unit_id = '" . (int)$filter_data['unit_id'] . "'";
    }

    if (!empty($filter_data['product_unit_filter'])) {
        $sql .= " AND pu.unit_id IN (" . implode(',', array_map('intval', $filter_data['product_unit_filter'])) . ")";
    }

    $query = $this->db->query($sql);

    foreach ($query->rows as $row) {
        $units[$row['product_id']][] = array(
            'unit_id'    => $row['unit_id'],
            'unit_name'       => $row['unit_name']
        );
    }

    return $units;
}



public function getAvailableQuantityForOnline($product_id, $unit_id) {
    $query = $this->db->query("SELECT quantity_available FROM " . DB_PREFIX . "product_inventory WHERE product_id = '" . (int)$product_id . "' AND unit_id = '" . (int)$unit_id . "'");
    $quantity = $query->row ? $query->row['quantity_available'] : 0;
    
    // دائمًا نحسب الكمية القابلة للتحويل، بغض النظر عن الكمية المتوفرة للوحدة المطلوبة
    $convertible_quantity = $this->getConvertibleQuantity($product_id, $unit_id);
    
    // نجمع الكمية المتوفرة مع الكمية القابلة للتحويل
    return $quantity + $convertible_quantity;
}

public function getConvertibleQuantity($product_id, $unit_id) {
    $units = $this->getProductUnits($product_id);
    $base_unit = $this->getDefaultUnit($units);
    
    // إذا كانت الوحدة المطلوبة هي الوحدة الأساسية
    if ($base_unit['unit_id'] == $unit_id) {
        $convertible_quantity = 0;
        foreach ($units as $unit) {
            if ($unit['unit_id'] != $unit_id && $unit['conversion_factor'] > 0) {
                $query = $this->db->query("SELECT quantity_available FROM " . DB_PREFIX . "product_inventory WHERE product_id = '" . (int)$product_id . "' AND unit_id = '" . (int)$unit['unit_id'] . "'");
                $available_quantity = $query->row ? $query->row['quantity_available'] : 0;
                $convertible_quantity += $available_quantity * $unit['conversion_factor'];
            }
        }
        return $convertible_quantity;
    }
    
    // إذا كانت الوحدة المطلوبة ليست الوحدة الأساسية
    $target_unit = array_filter($units, function($u) use ($unit_id) {
        return $u['unit_id'] == $unit_id;
    });
    $target_unit = reset($target_unit);
    
    // إذا كانت الوحدة المطلوبة مستقلة (معامل التحويل = 0)، لا يمكن التحويل إليها
    if (!$target_unit || $target_unit['conversion_factor'] == 0) {
        return 0;
    }
    
    // في حالة الوحدة غير الأساسية وغير المستقلة، نحسب الكمية القابلة للتحويل من الوحدة الأساسية
    $base_quantity = $this->getAvailableQuantityForOnline($product_id, $base_unit['unit_id']);
    return floor($base_quantity / $target_unit['conversion_factor']);
}

public function calculateQuantityDiscount($product_id, $unit_id, $quantity, $current_price) {
    $discounts = $this->getProductQuantityDiscounts($product_id,$quantity, $unit_id);
    $result = array(
        'final_price' => $current_price,
        'free_quantity' => 0,
        'next_discount' => null
    );

    $applicable_discount = null;
    foreach ($discounts as $discount) {
        if ($quantity >= $discount['buy_quantity']) {
            if (!$applicable_discount || $discount['buy_quantity'] > $applicable_discount['buy_quantity']) {
                $applicable_discount = $discount;
            }
        }
    }

    if ($applicable_discount) {
        if ($applicable_discount['type'] == 'percentage') {
            $result['final_price'] = $current_price * (1 - ($applicable_discount['discount_value'] / 100));
        } elseif ($applicable_discount['type'] == 'fixed') {
            $result['final_price'] = $current_price - $applicable_discount['discount_value'];
        } elseif ($applicable_discount['type'] == 'buy_x_get_y') {
            $sets = floor($quantity / $applicable_discount['buy_quantity']);
            $result['free_quantity'] = $sets * $applicable_discount['get_quantity'];
        }
    }

    // تحديد الخصم التالي
    foreach ($discounts as $discount) {
        if ($quantity < $discount['buy_quantity']) {
            if (!$result['next_discount'] || $discount['buy_quantity'] < $result['next_discount']['buy_quantity']) {
                $result['next_discount'] = $discount;
            }
        }
    }

    return $result;
}

public function getProductUnitSpecialPrice($product_id, $unit_id) {
    $query = $this->db->query("SELECT special_price FROM " . DB_PREFIX . "product_pricing WHERE product_id = '" . (int)$product_id . "' AND unit_id = '" . (int)$unit_id . "'");
    return $query->row ? $query->row['special_price'] : 0;
}


	public function getLatestProducts($limit) {
		$product_data = $this->cache->get('product.latest.' . (int)$this->config->get('config_language_id') . '.' . (int)$this->config->get('config_store_id') . '.' . $this->config->get('config_customer_group_id') . '.' . (int)$limit);

		if (!$product_data) {
			$product_data = array();
			$query = $this->db->query("SELECT p.product_id FROM " . DB_PREFIX . "product p LEFT JOIN " . DB_PREFIX . "product_to_store p2s ON (p.product_id = p2s.product_id) WHERE p.status = '1' AND p.date_available <= NOW() ORDER BY p.date_added DESC LIMIT " . (int)$limit);

			foreach ($query->rows as $result) {
				$product_data[$result['product_id']] = $this->getProduct($result['product_id']);
			}

			$this->cache->set('product.latest.' . (int)$this->config->get('config_language_id') . '.' . (int)$this->config->get('config_store_id') . '.' . $this->config->get('config_customer_group_id') . '.' . (int)$limit, $product_data);
		}

		return $product_data;
	}

	public function getPopularProducts($limit) {
		$product_data = $this->cache->get('product.popular.' . (int)$this->config->get('config_language_id') . '.' . (int)$this->config->get('config_store_id') . '.' . $this->config->get('config_customer_group_id') . '.' . (int)$limit);
	
		if (!$product_data) {
			$product_data = array();
			$query = $this->db->query("SELECT p.product_id FROM " . DB_PREFIX . "product p LEFT JOIN " . DB_PREFIX . "product_to_store p2s ON (p.product_id = p2s.product_id) WHERE p.status = '1' AND p.date_available <= NOW() ORDER BY p.viewed DESC, p.date_added DESC LIMIT " . (int)$limit);
	
			foreach ($query->rows as $result) {
				$product_data[$result['product_id']] = $this->getProduct($result['product_id']);
			}
			
			$this->cache->set('product.popular.' . (int)$this->config->get('config_language_id') . '.' . (int)$this->config->get('config_store_id') . '.' . $this->config->get('config_customer_group_id') . '.' . (int)$limit, $product_data);
		}
		
		return $product_data;
	}

	public function getBestSellerProducts($limit) {
		$product_data = $this->cache->get('product.bestseller.' . (int)$this->config->get('config_language_id') . '.' . (int)$this->config->get('config_store_id') . '.' . $this->config->get('config_customer_group_id') . '.' . (int)$limit);

		if (!$product_data) {
			$product_data = array();

			$query = $this->db->query("SELECT op.product_id, SUM(op.quantity) AS total FROM " . DB_PREFIX . "order_product op LEFT JOIN `" . DB_PREFIX . "order` o ON (op.order_id = o.order_id) LEFT JOIN `" . DB_PREFIX . "product` p ON (op.product_id = p.product_id) LEFT JOIN " . DB_PREFIX . "product_to_store p2s ON (p.product_id = p2s.product_id) WHERE o.order_status_id > '0' AND p.status = '1' AND p.date_available <= NOW()   GROUP BY op.product_id ORDER BY total DESC LIMIT " . (int)$limit);

			foreach ($query->rows as $result) {
				$product_data[$result['product_id']] = $this->getProduct($result['product_id']);
			}

			$this->cache->set('product.bestseller.' . (int)$this->config->get('config_language_id') . '.' . (int)$this->config->get('config_store_id') . '.' . $this->config->get('config_customer_group_id') . '.' . (int)$limit, $product_data);
		}

		return $product_data;
	}

	public function getProductAttributes($product_id) {
		$product_attribute_group_data = array();

		$product_attribute_group_query = $this->db->query("SELECT ag.attribute_group_id, agd.name FROM " . DB_PREFIX . "product_attribute pa LEFT JOIN " . DB_PREFIX . "attribute a ON (pa.attribute_id = a.attribute_id) LEFT JOIN " . DB_PREFIX . "attribute_group ag ON (a.attribute_group_id = ag.attribute_group_id) LEFT JOIN " . DB_PREFIX . "attribute_group_description agd ON (ag.attribute_group_id = agd.attribute_group_id) WHERE pa.product_id = '" . (int)$product_id . "' AND agd.language_id = '" . (int)$this->config->get('config_language_id') . "' GROUP BY ag.attribute_group_id ORDER BY ag.sort_order, agd.name");

		foreach ($product_attribute_group_query->rows as $product_attribute_group) {
			$product_attribute_data = array();

			$product_attribute_query = $this->db->query("SELECT a.attribute_id, ad.name, pa.text FROM " . DB_PREFIX . "product_attribute pa LEFT JOIN " . DB_PREFIX . "attribute a ON (pa.attribute_id = a.attribute_id) LEFT JOIN " . DB_PREFIX . "attribute_description ad ON (a.attribute_id = ad.attribute_id) WHERE pa.product_id = '" . (int)$product_id . "' AND a.attribute_group_id = '" . (int)$product_attribute_group['attribute_group_id'] . "' AND ad.language_id = '" . (int)$this->config->get('config_language_id') . "' AND pa.language_id = '" . (int)$this->config->get('config_language_id') . "' ORDER BY a.sort_order, ad.name");

			foreach ($product_attribute_query->rows as $product_attribute) {
				$product_attribute_data[] = array(
					'attribute_id' => $product_attribute['attribute_id'],
					'name'         => $product_attribute['name'],
					'text'         => $product_attribute['text']
				);
			}

			$product_attribute_group_data[] = array(
				'attribute_group_id' => $product_attribute_group['attribute_group_id'],
				'name'               => $product_attribute_group['name'],
				'attribute'          => $product_attribute_data
			);
		}

		return $product_attribute_group_data;
	}
public function loadQuantityDiscounts($product_id) {
    $query = $this->db->query("SELECT * FROM `cod_product_quantity_discounts` WHERE product_id = '" . (int)$product_id . "' AND status = '1'");
    return $query->rows;
}

public function getQuantityDiscounts($product_id) {
    $query = $this->db->query("SELECT * FROM `cod_product_quantity_discounts` WHERE product_id = '" . (int)$product_id . "' AND status = '1'");
    return $query->rows;
}


public function getProductTaxClass($product_id) {
    $query = $this->db->query("SELECT tax_class_id FROM " . DB_PREFIX . "product WHERE product_id = '" . (int)$product_id . "'");
    
    if ($query->num_rows) {
        return (int)$query->row['tax_class_id'];
    }

    return 0; // Default to 0 if no tax class is found
}


	public function getProductOptions($product_id) {
		$product_option_data = array();

		$product_option_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_option po LEFT JOIN `" . DB_PREFIX . "option` o ON (po.option_id = o.option_id) LEFT JOIN " . DB_PREFIX . "option_description od ON (o.option_id = od.option_id) WHERE po.product_id = '" . (int)$product_id . "' AND od.language_id = '" . (int)$this->config->get('config_language_id') . "' ORDER BY o.sort_order");

		foreach ($product_option_query->rows as $product_option) {
			$product_option_value_data = array();

			$product_option_value_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_option_value pov LEFT JOIN " . DB_PREFIX . "option_value ov ON (pov.option_value_id = ov.option_value_id) LEFT JOIN " . DB_PREFIX . "option_value_description ovd ON (ov.option_value_id = ovd.option_value_id) WHERE pov.product_id = '" . (int)$product_id . "' AND pov.product_option_id = '" . (int)$product_option['product_option_id'] . "' AND ovd.language_id = '" . (int)$this->config->get('config_language_id') . "' ORDER BY ov.sort_order");
                $product_info = $this->getProduct($product_id);

			foreach ($product_option_value_query->rows as $product_option_value) {
				$product_option_value_data[] = array(
					'product_option_value_id' => $product_option_value['product_option_value_id'],
					'option_value_id'         => $product_option_value['option_value_id'],
					'name'                    => $product_option_value['name'],
					'image'                   => $product_option_value['image'],
					'quantity'                => $product_option_value['quantity'],
					'subtract'                => $product_option_value['subtract'],
					'price'                   => round($this->tax->calculate(
                                            $product_option_value['price'], 
                                            $product_info['tax_class_id'], 
                                            true
                                        ), 2),
                    'price_prefix'            => $product_option_value['price_prefix'],
					'weight'                  => $product_option_value['weight'],
					'weight_prefix'           => $product_option_value['weight_prefix']
				);
			}

			$product_option_data[] = array(
				'product_option_id'    => $product_option['product_option_id'],
				'product_option_value' => $product_option_value_data,
				'option_id'            => $product_option['option_id'],
				'unit_id'            => $product_option['unit_id'],
				'name'                 => $product_option['name'],
				'type'                 => $product_option['type'],
				'value'                => $product_option['value'],
				'required'             => $product_option['required']
			);
		}

		return $product_option_data;
	}

public function getProductOptionsByUnit($product_id, $unit_id) {
    $product_option_data = array();

    // استعلام لجلب الخيارات الأساسية
    $product_option_query = $this->db->query("
        SELECT po.*, o.type, od.name 
        FROM " . DB_PREFIX . "product_option po 
        LEFT JOIN `" . DB_PREFIX . "option` o ON (po.option_id = o.option_id) 
        LEFT JOIN " . DB_PREFIX . "option_description od ON (o.option_id = od.option_id) 
        WHERE po.product_id = '" . (int)$product_id . "' 
        AND od.language_id = '" . (int)$this->config->get('config_language_id') . "' 
        AND (po.unit_id = '" . (int)$unit_id . "' OR po.unit_id = 0) 
        ORDER BY o.sort_order
    ");

    // استعلام لجلب معلومات المنتج
    $product_info = $this->getProduct($product_id);

    foreach ($product_option_query->rows as $product_option) {
        $product_option_value_data = array();

        // استعلام لجلب قيم الخيار المرتبطة
        $product_option_value_query = $this->db->query("
            SELECT pov.*, ov.image, ovd.name 
            FROM " . DB_PREFIX . "product_option_value pov 
            LEFT JOIN " . DB_PREFIX . "option_value ov ON (pov.option_value_id = ov.option_value_id) 
            LEFT JOIN " . DB_PREFIX . "option_value_description ovd ON (ov.option_value_id = ovd.option_value_id) 
            WHERE pov.product_id = '" . (int)$product_id . "' 
            AND pov.product_option_id = '" . (int)$product_option['product_option_id'] . "' 
            AND ovd.language_id = '" . (int)$this->config->get('config_language_id') . "' 
            ORDER BY ov.sort_order
        ");

        foreach ($product_option_value_query->rows as $product_option_value) {
            $product_option_value_data[] = array(
                'product_option_value_id' => $product_option_value['product_option_value_id'],
                'option_value_id'         => $product_option_value['option_value_id'],
                'name'                    => $product_option_value['name'],
                'image'                   => $product_option_value['image'],
                'quantity'                => $product_option_value['quantity'],
                'subtract'                => $product_option_value['subtract'],
                'price'                   => round($this->tax->calculate(
                    $product_option_value['price'], 
                    $product_info['tax_class_id'], 
                    true
                ), 2),
                'price_prefix'            => $product_option_value['price_prefix'],
                'weight'                  => $product_option_value['weight'],
                'weight_prefix'           => $product_option_value['weight_prefix']
            );
        }

        $product_option_data[] = array(
            'product_option_id'    => $product_option['product_option_id'],
            'product_option_value' => $product_option_value_data,
            'option_id'            => $product_option['option_id'],
            'unit_id'              => $product_option['unit_id'],
            'name'                 => $product_option['name'],
            'type'                 => $product_option['type'],
            'value'                => $product_option['value'],
            'required'             => $product_option['required']
        );
    }

    return $product_option_data;
}

public function getOptionPrice($product_id, $option_id, $option_value_id) {
    // لاحظ أننا نضمّ ovd.name
    $sql = "SELECT
                pov.price,
                pov.price_prefix,
                ovd.name AS option_value_name
            FROM " . DB_PREFIX . "product_option_value pov
            LEFT JOIN " . DB_PREFIX . "option_value_description ovd
                   ON (pov.option_value_id = ovd.option_value_id)
            WHERE pov.product_id = '" . (int)$product_id . "'
              AND pov.product_option_id = '" . (int)$option_id . "'
              AND pov.product_option_value_id = '" . (int)$option_value_id . "'
              AND ovd.language_id = '" . (int)$this->config->get('config_language_id') . "'
            LIMIT 1";

    $query = $this->db->query($sql);

    if ($query->num_rows) {
        // مثلاً:
        $price = (float)$query->row['price'];
        $price_prefix = $query->row['price_prefix'];

        // وهنا بدلاً من row['name'] نستخدم row['option_value_name']
        $optionValueName = $query->row['option_value_name'] ?: '';

        if ($price_prefix == '+') {
            return array('name' => $optionValueName, 'price_prefix' => '+', 'price' => $price);
        } elseif ($price_prefix == '-') {
            return array('name' => $optionValueName, 'price_prefix' => '-', 'price' => $price);
        } elseif ($price_prefix == '=') {
            return array('name' => $optionValueName, 'price_prefix' => '=', 'price' => $price);
        } else {
            return array('name' => $optionValueName, 'price_prefix' => null, 'price' => 0);
        }
    }

    // لم نجد أي صف يطابق
    return array('name' => null, 'price_prefix' => null, 'price' => 0);
}


public function getOptionPriceWithTax($product_id, $option_id, $option_value_id) {
    $sql = "SELECT pov.price, pov.price_prefix 
            FROM " . DB_PREFIX . "product_option_value pov 
            WHERE pov.product_id = '" . (int)$product_id . "' 
            AND pov.product_option_id = '" . (int)$option_id . "' 
            AND pov.product_option_value_id = '" . (int)$option_value_id . "'";
    
    $query = $this->db->query($sql);
            // جلب معلومات المنتج
            $product_info = $this->getProduct($product_id);
    if ($query->num_rows) {
        if (isset($query->row['price']) && isset($query->row['price_prefix'])) {
            $price = (float)$query->row['price'];
            $option_price_with_tax =  ($price > 0) ? round($this->tax->calculate($price, $product_info['tax_class_id'], true), 2) : 0;
            $price_prefix = $query->row['price_prefix'];
            if ($price_prefix == '+') {
                return array('name' => $query->row['name'], 'price_prefix' => '+', 'price' => $option_price_with_tax);
            } elseif ($price_prefix == '-') {
                return array('name' => $query->row['name'], 'price_prefix' => '-', 'price' => $option_price_with_tax);
            } elseif ($price_prefix == '=') {
                return array('name' => $query->row['name'], 'price_prefix' => '=', 'price' => $option_price_with_tax);
            } else {
                return array('name' => $query->row['name'], 'price_prefix' => null, 'price' => 0);
            }
        } else {
            return array('name' => null, 'price_prefix' => null, 'price' => 0);
        }
    }

    return array('name' => null, 'price_prefix' => null, 'price' => 0);
}
	public function getProductImages($product_id) {
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_image WHERE product_id = '" . (int)$product_id . "' ORDER BY sort_order ASC");

		return $query->rows;
	}

	public function getProductRelated($product_id) {
		$product_data = array();

		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_related pr LEFT JOIN " . DB_PREFIX . "product p ON (pr.related_id = p.product_id) LEFT JOIN " . DB_PREFIX . "product_to_store p2s ON (p.product_id = p2s.product_id) WHERE pr.product_id = '" . (int)$product_id . "' AND p.status = '1' AND p.date_available <= NOW()  ");

		foreach ($query->rows as $result) {
			$product_data[$result['related_id']] = $this->getProduct($result['related_id']);
		}

		return $product_data;
	}

	public function getProductLayoutId($product_id) {
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_to_layout WHERE product_id = '" . (int)$product_id . "' AND store_id = '" . (int)$this->config->get('config_store_id') . "'");

		if ($query->num_rows) {
			return (int)$query->row['layout_id'];
		} else {
			return 0;
		}
	}

	public function getCategories(int $product_id): array {
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "product_to_category` WHERE `product_id` = '" . (int)$product_id . "'");

		return $query->rows;
	}

	public function getAttributes(int $product_id): array {
		$product_attribute_group_data = [];

		$product_attribute_group_query = $this->db->query("SELECT ag.`attribute_group_id`, agd.`name` FROM `" . DB_PREFIX . "product_attribute` pa LEFT JOIN `" . DB_PREFIX . "attribute` a ON (pa.`attribute_id` = a.`attribute_id`) LEFT JOIN `" . DB_PREFIX . "attribute_group` ag ON (a.`attribute_group_id` = ag.`attribute_group_id`) LEFT JOIN `" . DB_PREFIX . "attribute_group_description` agd ON (ag.`attribute_group_id` = agd.`attribute_group_id`) WHERE pa.`product_id` = '" . (int)$product_id . "' AND agd.`language_id` = '" . (int)$this->config->get('config_language_id') . "' GROUP BY ag.`attribute_group_id` ORDER BY ag.`sort_order`, agd.`name`");

		foreach ($product_attribute_group_query->rows as $product_attribute_group) {
			$product_attribute_data = [];

			$product_attribute_query = $this->db->query("SELECT a.`attribute_id`, ad.`name`, pa.`text` FROM `" . DB_PREFIX . "product_attribute` pa LEFT JOIN `" . DB_PREFIX . "attribute` a ON (pa.`attribute_id` = a.`attribute_id`) LEFT JOIN `" . DB_PREFIX . "attribute_description` ad ON (a.`attribute_id` = ad.`attribute_id`) WHERE pa.`product_id` = '" . (int)$product_id . "' AND a.`attribute_group_id` = '" . (int)$product_attribute_group['attribute_group_id'] . "' AND ad.`language_id` = '" . (int)$this->config->get('config_language_id') . "' AND pa.`language_id` = '" . (int)$this->config->get('config_language_id') . "' ORDER BY a.`sort_order`, ad.`name`");

			foreach ($product_attribute_query->rows as $product_attribute) {
				$product_attribute_data[] = [
					'attribute_id' => $product_attribute['attribute_id'],
					'name'         => $product_attribute['name'],
					'text'         => $product_attribute['text']
				];
			}

			$product_attribute_group_data[] = [
				'attribute_group_id' => $product_attribute_group['attribute_group_id'],
				'name'               => $product_attribute_group['name'],
				'attribute'          => $product_attribute_data
			];
		}

		return $product_attribute_group_data;
	}
	

    
 
	public function getOptions(int $product_id): array {
		$product_option_data = [];

		$product_option_query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "product_option` `po` LEFT JOIN `" . DB_PREFIX . "option` o ON (po.`option_id` = o.`option_id`) LEFT JOIN `" . DB_PREFIX . "option_description` od ON (o.`option_id` = od.`option_id`) WHERE po.`product_id` = '" . (int)$product_id . "' AND od.`language_id` = '" . (int)$this->config->get('config_language_id') . "' ORDER BY o.`sort_order`");

		foreach ($product_option_query->rows as $product_option) {
			$product_option_value_data = [];

			$product_option_value_query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "product_option_value` pov LEFT JOIN `" . DB_PREFIX . "option_value` ov ON (pov.`option_value_id` = ov.`option_value_id`) LEFT JOIN `" . DB_PREFIX . "option_value_description` ovd ON (ov.`option_value_id` = ovd.`option_value_id`) WHERE pov.`product_id` = '" . (int)$product_id . "' AND pov.`product_option_id` = '" . (int)$product_option['product_option_id'] . "' AND ovd.`language_id` = '" . (int)$this->config->get('config_language_id') . "' ORDER BY ov.`sort_order`");
                $product_info = $this->getProduct($product_id);
			foreach ($product_option_value_query->rows as $product_option_value) {

        
                    $product_option_value_data[] = [
					'product_option_value_id' => $product_option_value['product_option_value_id'],
					'option_value_id'         => $product_option_value['option_value_id'],
					'name'                    => $product_option_value['name'],
					'image'                   => $product_option_value['image'],
					'quantity'                => $product_option_value['quantity'],
					'subtract'                => $product_option_value['subtract'],
					'price'                   => round($this->tax->calculate(
                                            $product_option_value['price'], 
                                            $product_info['tax_class_id'], 
                                            true
                                        ), 2),
					'price_prefix'            => $product_option_value['price_prefix'],
					'weight'                  => $product_option_value['weight'],
					'weight_prefix'           => $product_option_value['weight_prefix']
				];
                                 
                
                

			}
            if(!empty($product_option_value_data)){
			$product_option_data[] = [
				'product_option_id'    => $product_option['product_option_id'],
				'product_option_value' => $product_option_value_data,
				'option_id'            => $product_option['option_id'],
				'unit_id'            => $product_option['unit_id'],
				'name'                 => $product_option['name'],
				'type'                 => $product_option['type'],
				'value'                => $product_option['value'],
				'required'             => $product_option['required']
			];
            }
			
		}

		return $product_option_data;
	}

	public function getTotalProducts($data = array()) {
		$sql = "SELECT COUNT(DISTINCT p.product_id) AS total";

		if (!empty($data['filter_category_id'])) {
			if (!empty($data['filter_sub_category'])) {
				$sql .= " FROM " . DB_PREFIX . "category_path cp LEFT JOIN " . DB_PREFIX . "product_to_category p2c ON (cp.category_id = p2c.category_id)";
			} else {
				$sql .= " FROM " . DB_PREFIX . "product_to_category p2c";
			}

			if (!empty($data['filter_filter'])) {
				$sql .= " LEFT JOIN " . DB_PREFIX . "product_filter pf ON (p2c.product_id = pf.product_id) LEFT JOIN " . DB_PREFIX . "product p ON (pf.product_id = p.product_id)";
			} else {
				$sql .= " LEFT JOIN " . DB_PREFIX . "product p ON (p2c.product_id = p.product_id)";
			}
		} else {
			$sql .= " FROM " . DB_PREFIX . "product p";
		}

		$sql .= " LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id) LEFT JOIN " . DB_PREFIX . "product_to_store p2s ON (p.product_id = p2s.product_id) WHERE pd.language_id = '" . (int)$this->config->get('config_language_id') . "' AND p.status = '1' AND p.date_available <= NOW()  ";

		if (!empty($data['filter_category_id'])) {
			if (!empty($data['filter_sub_category'])) {
				$sql .= " AND cp.path_id = '" . (int)$data['filter_category_id'] . "'";
			} else {
				$sql .= " AND p2c.category_id = '" . (int)$data['filter_category_id'] . "'";
			}

			if (!empty($data['filter_filter'])) {
				$implode = array();

				$filters = explode(',', $data['filter_filter']);

				foreach ($filters as $filter_id) {
					$implode[] = (int)$filter_id;
				}

				$sql .= " AND pf.filter_id IN (" . implode(',', $implode) . ")";
			}
		}

		if (!empty($data['filter_name']) || !empty($data['filter_tag'])) {
			$sql .= " AND (";

			if (!empty($data['filter_name'])) {
				$implode = array();

				$words = explode(' ', trim(preg_replace('/\s+/', ' ', $data['filter_name'])));

				foreach ($words as $word) {
					$implode[] = "pd.name LIKE '%" . $this->db->escape($word) . "%'";
				}

				if ($implode) {
					$sql .= " " . implode(" AND ", $implode) . "";
				}

				if (!empty($data['filter_description'])) {
					$sql .= " OR pd.description LIKE '%" . $this->db->escape($data['filter_name']) . "%'";
				}
			}

			if (!empty($data['filter_name']) && !empty($data['filter_tag'])) {
				$sql .= " OR ";
			}

			if (!empty($data['filter_tag'])) {
				$implode = array();

				$words = explode(' ', trim(preg_replace('/\s+/', ' ', $data['filter_tag'])));

				foreach ($words as $word) {
					$implode[] = "pd.tag LIKE '%" . $this->db->escape($word) . "%'";
				}

				if ($implode) {
					$sql .= " " . implode(" AND ", $implode) . "";
				}
			}

			if (!empty($data['filter_name'])) {
				$sql .= " OR LCASE(p.model) = '" . $this->db->escape(utf8_strtolower($data['filter_name'])) . "'";
				$sql .= " OR LCASE(p.sku) = '" . $this->db->escape(utf8_strtolower($data['filter_name'])) . "'";
				$sql .= " OR LCASE(p.upc) = '" . $this->db->escape(utf8_strtolower($data['filter_name'])) . "'";
				$sql .= " OR LCASE(p.ean) = '" . $this->db->escape(utf8_strtolower($data['filter_name'])) . "'";
				$sql .= " OR LCASE(p.jan) = '" . $this->db->escape(utf8_strtolower($data['filter_name'])) . "'";
				$sql .= " OR LCASE(p.isbn) = '" . $this->db->escape(utf8_strtolower($data['filter_name'])) . "'";
				$sql .= " OR LCASE(p.mpn) = '" . $this->db->escape(utf8_strtolower($data['filter_name'])) . "'";
			}

			$sql .= ")";
		}

		if (!empty($data['filter_manufacturer_id'])) {
			$sql .= " AND p.manufacturer_id = '" . (int)$data['filter_manufacturer_id'] . "'";
		}

		$query = $this->db->query($sql);

		return $query->row['total'];
	}






//تم تغيير الدالة الدالة بجداول جديدة
	public function getTotalProductSpecials() {
		$query = $this->db->query("SELECT COUNT(DISTINCT ps.product_id) AS total FROM " . DB_PREFIX . "product_special ps LEFT JOIN " . DB_PREFIX . "product p ON (ps.product_id = p.product_id) LEFT JOIN " . DB_PREFIX . "product_to_store p2s ON (p.product_id = p2s.product_id) WHERE p.status = '1' AND p.date_available <= NOW()   AND ps.customer_group_id = '" . (int)$this->config->get('config_customer_group_id') . "' AND ((ps.date_start = '0000-00-00' OR ps.date_start < NOW()) AND (ps.date_end = '0000-00-00' OR ps.date_end > NOW()))");

		if (isset($query->row['total'])) {
			return $query->row['total'];
		} else {
			return 0;
		}
	}

	public function checkProductCategory($product_id, $category_ids) {
		
		$implode = array();

		foreach ($category_ids as $category_id) {
			$implode[] = (int)$category_id;
		}
		
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_to_category WHERE product_id = '" . (int)$product_id . "' AND category_id IN(" . implode(',', $implode) . ")");
  	    return $query->row;
	}
}