<?php
namespace Cart;

class Cart {
    private $data = [];
    private $config;
    private $customer;
    private $session;
    private $db;
    private $tax;
    private $weight;

    public function __construct($registry) {
        // ربط الخصائص من الـregistry
        $this->config   = $registry->get('config');
        $this->customer = $registry->get('customer');
        $this->session  = $registry->get('session');
        $this->db       = $registry->get('db');
        $this->tax      = $registry->get('tax');
        $this->weight   = $registry->get('weight');

        // حذف السجلات القديمة (العناصر المنتهية) قبل ساعتين
        $this->db->query("
            DELETE FROM " . DB_PREFIX . "cart
            WHERE (api_id > '0' OR customer_id = '0')
              AND date_added < DATE_SUB(NOW(), INTERVAL 2 HOUR)
        ");

        // دمج سلة ما قبل تسجيل الدخول (إن كان العميل سجل الآن)
        if ($this->customer->getId()) {
            // تحديث session_id لأي عناصر كانت للعميل نفسه
            $this->db->query("
                UPDATE " . DB_PREFIX . "cart
                SET session_id = '" . $this->db->escape($this->session->getId()) . "'
                WHERE api_id = '0'
                  AND customer_id = '" . (int)$this->customer->getId() . "'
            ");

            // البحث عن أي عناصر كانت في جلسة ضيف وإضافتها للعميل الحالي
            $cart_query = $this->db->query("
                SELECT *
                FROM " . DB_PREFIX . "cart
                WHERE api_id      = '0'
                  AND customer_id = '0'
                  AND session_id  = '" . $this->db->escape($this->session->getId()) . "'
            ");

            foreach ($cart_query->rows as $cart) {
                // احذف القديم
                $this->db->query("
                    DELETE FROM " . DB_PREFIX . "cart
                    WHERE cart_id = '" . (int)$cart['cart_id'] . "'
                ");

                // أعد إضافته تحت حساب العميل الحالي
                $this->add(
                    $cart['product_id'],
                    $cart['quantity'],
                    json_decode($cart['option'], true),
                    $cart['unit_id'],
                    $cart['price'],
                    (bool)$cart['is_free'],
                    $cart['bundle_id'] ?: null,
                    $cart['product_quantity_discount_id'] ?: null,
                    $cart['group_id'] ?: null,
                    // استرجاع selected_bundles/bundle_options إذا كانت موجودة
                    $cart['selected_bundles'] ? json_decode($cart['selected_bundles'], true) : [],
                    $cart['bundle_options']   ? json_decode($cart['bundle_options'], true)   : []
                );
            }
        }
    }

    /**
     * جلب كل منتجات السلة وتجهيز بياناتها للعرض/الحساب.
     */
    public function getProducts(): array {
        $product_data = [];

        $api_id      = isset($this->session->data['api_id']) ? (int)$this->session->data['api_id'] : 0;
        $customer_id = (int)$this->customer->getId();
        $session_id  = $this->db->escape($this->session->getId());

        // جلب سجلات السلة
        $cart_query = $this->db->query("
            SELECT *
            FROM " . DB_PREFIX . "cart
            WHERE api_id      = '{$api_id}'
              AND customer_id = '{$customer_id}'
              AND session_id  = '{$session_id}'
        ");

        foreach ($cart_query->rows as $cart) {
            $stock = true;

            // جلب بيانات المنتج الأساسية
            $product_query = $this->db->query("
                SELECT p.*, pd.name, pd.description
                FROM " . DB_PREFIX . "product p
                LEFT JOIN " . DB_PREFIX . "product_description pd
                       ON (p.product_id = pd.product_id)
                WHERE p.product_id = '" . (int)$cart['product_id'] . "'
                  AND pd.language_id = '" . (int)$this->config->get('config_language_id') . "'
                  AND p.date_available <= NOW()
                  AND p.status = '1'
            ");

            if ($product_query->num_rows && $cart['quantity'] > 0) {
                $product_info = $product_query->row;

                $option_data   = [];
                $option_price  = 0.0;
                $option_points = 0;
                $option_weight = 0.0;

                // خيارات المنتج (من حقل option في الجدول)
                $cart_option = !empty($cart['option'])
                               ? json_decode($cart['option'], true)
                               : [];

                // تحليل الخيارات
                if (is_array($cart_option)) {
                    foreach ($cart_option as $product_option_id => $value) {
                        $option_query = $this->db->query("
                            SELECT 
                                po.product_option_id,
                                po.option_id,
                                od.name,
                                o.type
                            FROM " . DB_PREFIX . "product_option po
                            LEFT JOIN " . DB_PREFIX . "option o
                                   ON (po.option_id = o.option_id)
                            LEFT JOIN " . DB_PREFIX . "option_description od
                                   ON (o.option_id = od.option_id)
                            WHERE po.product_option_id = '" . (int)$product_option_id . "'
                              AND po.product_id        = '" . (int)$cart['product_id'] . "'
                              AND od.language_id       = '" . (int)$this->config->get('config_language_id') . "'
                        ");

                        if ($option_query->num_rows) {
                            $oType = $option_query->row['type'];
                            $oName = $option_query->row['name'];

                            // في حال select/radio/checkbox
                            if (in_array($oType, ['select','radio','checkbox'])) {
                                $values = is_array($value) ? $value : [$value];

                                foreach ($values as $val) {
                                    $option_value_query = $this->db->query("
                                        SELECT 
                                            pov.option_value_id,
                                            ovd.name,
                                            pov.quantity,
                                            pov.subtract,
                                            pov.price,
                                            pov.price_prefix,
                                            pov.points,
                                            pov.points_prefix,
                                            pov.weight,
                                            pov.weight_prefix
                                        FROM " . DB_PREFIX . "product_option_value pov
                                        LEFT JOIN " . DB_PREFIX . "option_value ov
                                               ON (pov.option_value_id = ov.option_value_id)
                                        LEFT JOIN " . DB_PREFIX . "option_value_description ovd
                                               ON (ov.option_value_id = ovd.option_value_id)
                                        WHERE pov.product_option_value_id = '" . (int)$val . "'
                                          AND pov.product_option_id = '" . (int)$product_option_id . "'
                                          AND ovd.language_id       = '" . (int)$this->config->get('config_language_id') . "'
                                    ");

                                    if ($option_value_query->num_rows) {
                                        $ov_row = $option_value_query->row;

                                        // تأثير السعر
                                        if ($ov_row['price_prefix'] === '+') {
                                            $option_price += (float)$ov_row['price'];
                                        } elseif ($ov_row['price_prefix'] === '-') {
                                            $option_price -= (float)$ov_row['price'];
                                        } elseif ($ov_row['price_prefix'] === '=') {
                                            $option_price  = (float)$ov_row['price'];
                                        }

                                        // النقاط
                                        if ($ov_row['points_prefix'] === '+') {
                                            $option_points += (int)$ov_row['points'];
                                        } elseif ($ov_row['points_prefix'] === '-') {
                                            $option_points -= (int)$ov_row['points'];
                                        }

                                        // الوزن
                                        if ($ov_row['weight_prefix'] === '+') {
                                            $option_weight += (float)$ov_row['weight'];
                                        } elseif ($ov_row['weight_prefix'] === '-') {
                                            $option_weight -= (float)$ov_row['weight'];
                                        }

                                        // التحقق من subtract
                                        if ($ov_row['subtract'] && $ov_row['quantity'] < $cart['quantity']) {
                                            $stock = false;
                                        }

                                        $option_data[] = [
                                            'product_option_id'       => $product_option_id,
                                            'product_option_value_id' => $val,
                                            'option_id'               => $option_query->row['option_id'],
                                            'option_value_id'         => $ov_row['option_value_id'],
                                            'name'                    => $oName,
                                            'value'                   => $ov_row['name'],
                                            'type'                    => $oType,
                                            'quantity'                => $ov_row['quantity'],
                                            'subtract'                => $ov_row['subtract'],
                                            'price'                   => $ov_row['price'],
                                            'price_prefix'            => $ov_row['price_prefix'],
                                            'points'                  => $ov_row['points'],
                                            'points_prefix'           => $ov_row['points_prefix'],
                                            'weight'                  => $ov_row['weight'],
                                            'weight_prefix'           => $ov_row['weight_prefix']
                                        ];
                                    }
                                }
                            }
                            elseif (in_array($oType, ['text','textarea','file','date','datetime','time'])) {
                                // نصي إلخ
                                $option_data[] = [
                                    'product_option_id'       => $product_option_id,
                                    'product_option_value_id' => '',
                                    'option_id'               => $option_query->row['option_id'],
                                    'option_value_id'         => '',
                                    'name'                    => $oName,
                                    'value'                   => $value,
                                    'type'                    => $oType,
                                    'quantity'                => '',
                                    'subtract'                => '',
                                    'price'                   => '',
                                    'price_prefix'            => '',
                                    'points'                  => '',
                                    'points_prefix'           => '',
                                    'weight'                  => '',
                                    'weight_prefix'           => ''
                                ];
                            }
                        }
                    }
                }

                // تحديد السعر الأساسي/الخاص
                $base_price    = (float)$this->getProductUnitPrice($cart['product_id'], (int)$cart['unit_id']);
                $special_price = (float)$this->getProductUnitSpecialPrice($cart['product_id'], (int)$cart['unit_id']);
                $final_price   = 0.0;

                // لو مخزن price => نعتمده
                if ($cart['price'] !== null) {
                    $final_price = (float)$cart['price'];
                } else {
                    // اختار base أم special
                    if ($special_price > 0 && $special_price < $base_price) {
                        $final_price = $special_price;
                    } else {
                        $final_price = $base_price;
                    }
                    $final_price += $option_price;
                }

                // تحقق من الكمية
                $quantity_available = $this->getAvailableQuantityForOnline($cart['product_id'], (int)$cart['unit_id']);
                if ($quantity_available < $cart['quantity']) {
                    $stock = false;
                }

                // تجهيز بيانات المنتج
                $product_data[] = [
                    'cart_id'  => $cart['cart_id'],
                    'product_id' => (int)$cart['product_id'],
                    'name'     => $product_info['name'],
                    'model'    => $product_info['model'],
                    'shipping' => $product_info['shipping'],
                    'image'    => $product_info['image'],
                    'option'   => $option_data,
                    'quantity' => (int)$cart['quantity'],
                    'unit_id'  => (int)$cart['unit_id'],
                    'minimum'  => $product_info['minimum'],
                    'subtract' => $product_info['subtract'],
                    'stock'    => $stock,

                    'price'    => $final_price,
                    'total'    => $final_price * (int)$cart['quantity'],

                    'reward' => !empty($product_info['reward'])
                                 ? ($product_info['reward'] * (int)$cart['quantity'])
                                 : 0,
                    'points' => !empty($product_info['points'])
                                 ? (($product_info['points'] + $option_points) * (int)$cart['quantity'])
                                 : 0,

                    'tax_class_id' => $product_info['tax_class_id'],

                    'weight'          => ($product_info['weight'] + $option_weight) * (int)$cart['quantity'],
                    'weight_class_id' => $product_info['weight_class_id'],
                    'length'          => $product_info['length'],
                    'width'           => $product_info['width'],
                    'height'          => $product_info['height'],
                    'length_class_id' => $product_info['length_class_id'],

                    'quantity_available' => $quantity_available,

                    'bundle_id'                    => $cart['bundle_id'] ?: null,
                    'product_quantity_discount_id' => $cart['product_quantity_discount_id'] ?: null,
                    'is_free' => (bool)$cart['is_free'],

                    // الحقول الإضافية
                    'group_id' => $cart['group_id'] ?: null,
                    // نخزن حقلي selected_bundles و bundle_options كنص JSON في الجدول:
                    'selected_bundles' => $cart['selected_bundles']
                                          ? json_decode($cart['selected_bundles'], true)
                                          : [],
                    'bundle_options'   => $cart['bundle_options']
                                          ? json_decode($cart['bundle_options'], true)
                                          : []
                ];
            } else {
                // المنتج غير موجود أو qty=0 => نحذفه
                $this->remove($cart['cart_id']);
            }
        }

        return $product_data;
    }

    /**
     * جلب السعر الأساسي (base_price)
     */
    protected function getProductUnitPrice($product_id, $unit_id): float {
        $query = $this->db->query("
            SELECT base_price
            FROM " . DB_PREFIX . "product_pricing
            WHERE product_id = '" . (int)$product_id . "'
              AND unit_id    = '" . (int)$unit_id . "'
            LIMIT 1
        ");
        return $query->num_rows ? (float)$query->row['base_price'] : 0.0;
    }

    /**
     * جلب السعر الخاص (special_price) إن وُجد
     */
    protected function getProductUnitSpecialPrice($product_id, $unit_id): float {
        $query = $this->db->query("
            SELECT special_price
            FROM " . DB_PREFIX . "product_pricing
            WHERE product_id = '" . (int)$product_id . "'
              AND unit_id    = '" . (int)$unit_id . "'
            LIMIT 1
        ");
        return $query->num_rows ? (float)$query->row['special_price'] : 0.0;
    }

    /**
     * جلب اسم الوحدة unit_name
     */
    public function getUnitName($unit_id) {
        $q = $this->db->query("
            SELECT CONCAT(desc_en, ' - ', desc_ar) AS unit_name
            FROM " . DB_PREFIX . "unit
            WHERE unit_id = '" . (int)$unit_id . "'
        ");
        return $q->num_rows ? $q->row['unit_name'] : '';
    }

    /**
     * جلب بيانات وحدة كاملة
     */
    public function getUnit($unit_id) {
        $q = $this->db->query("
            SELECT CONCAT(desc_en, ' - ', desc_ar) AS unit_name, code, unit_id
            FROM " . DB_PREFIX . "unit
            WHERE unit_id = '" . (int)$unit_id . "'
        ");
        return $q->num_rows ? $q->row : null;
    }

    /**
     * جلب الوحدات المتوفرة للمنتج
     */
    public function getProductUnits($product_id) {
        $q = $this->db->query("
            SELECT pu.*, CONCAT(u.desc_en, ' - ', u.desc_ar) AS unit_name
            FROM " . DB_PREFIX . "product_unit pu
            LEFT JOIN " . DB_PREFIX . "unit u
                   ON (pu.unit_id = u.unit_id)
            WHERE pu.product_id = '" . (int)$product_id . "'
        ");
        return $q->rows;
    }

    /**
     * حساب الكمية المتوفرة + الكمية القابلة للتحويل
     */
    protected function getAvailableQuantityForOnline($product_id, $unit_id): float {
        $q = $this->db->query("
            SELECT quantity_available
            FROM " . DB_PREFIX . "product_inventory
            WHERE product_id = '" . (int)$product_id . "'
              AND unit_id    = '" . (int)$unit_id . "'
        ");
        $direct = $q->num_rows ? (float)$q->row['quantity_available'] : 0.0;

        // إن أردت إضافة الكميات القابلة للتحويل من وحدات أخرى
        $convertible = $this->getConvertibleQuantity($product_id, $unit_id);

        return $direct + $convertible;
    }

    /**
     * البحث عن الوحدة الأساسية base في مصفوفة الوحدات
     */
    public function getDefaultUnit(array $units) {
        foreach ($units as $u) {
            if (!empty($u['unit_type']) && $u['unit_type'] === 'base') {
                return $u;
            }
        }
        // fallback
        return $units[0] ?? [
            'unit_id'           => 37,
            'unit_type'         => 'base',
            'conversion_factor' => 1,
            'unit_name'         => 'Default Unit'
        ];
    }

    /**
     * حساب الكمية القابلة للتحويل من الوحدات الأخرى
     */
    public function getConvertibleQuantity($product_id, $unit_id) {
        $units     = $this->getProductUnits($product_id);
        $base_unit = $this->getDefaultUnit($units);

        // لو طلب نفس الـbase
        if ($base_unit['unit_id'] == $unit_id) {
            $conv = 0.0;
            foreach ($units as $u) {
                if ($u['unit_id'] != $unit_id && !empty($u['conversion_factor']) && $u['conversion_factor'] > 0) {
                    $q = $this->db->query("
                        SELECT quantity_available
                        FROM " . DB_PREFIX . "product_inventory
                        WHERE product_id = '" . (int)$product_id . "'
                          AND unit_id    = '" . (int)$u['unit_id'] . "'
                    ");
                    $avail_q = $q->num_rows ? (float)$q->row['quantity_available'] : 0.0;
                    $conv   += ($avail_q * $u['conversion_factor']);
                }
            }
            return $conv;
        }

        // إذا ليست الوحدة الأساسية
        $target_unit = null;
        foreach ($units as $u) {
            if ($u['unit_id'] == $unit_id) {
                $target_unit = $u;
                break;
            }
        }
        if (!$target_unit || empty($target_unit['conversion_factor'])) {
            return 0.0;
        }

        // احسب بتحويل الكمية من الـbase
        $base_qty = $this->getAvailableQuantityForOnline($product_id, $base_unit['unit_id']);
        return floor($base_qty / $target_unit['conversion_factor']);
    }

    /**
     * إضافة منتج إلى السلة
     */
    public function add(
        $product_id,
        $quantity  = 1,
        $option    = [],
        $unit_id   = 37,
        $price     = null,
        $is_free   = false,
        $bundle_id = null,
        $pqd_id    = null,  // product_quantity_discount_id
        $group_id  = null,
        $selected_bundles = [], // JSON
        $bundle_options   = []  // JSON
    ) {
        $api_id      = isset($this->session->data['api_id']) ? (int)$this->session->data['api_id'] : 0;
        $customer_id = (int)$this->customer->getId();
        $session_id  = $this->db->escape($this->session->getId());

        // تحويل options إلى JSON
        $option_json  = $this->db->escape(json_encode($option));
        $is_free_int  = $is_free ? 1 : 0;

        // لو bundle_id أو pqd_id أو group_id = null => لا ندخلها في الـ WHERE
        $bundle_sql = ($bundle_id === null)
            ? " AND (bundle_id IS NULL)"
            : " AND (bundle_id = '" . (int)$bundle_id . "')";

        $pqd_sql = ($pqd_id === null)
            ? " AND (product_quantity_discount_id IS NULL)"
            : " AND (product_quantity_discount_id = '" . (int)$pqd_id . "')";

        $group_sql = ($group_id === null)
            ? " AND (group_id IS NULL)"
            : " AND (group_id = '" . $this->db->escape($group_id) . "')";

        // حقول selected_bundles, bundle_options
        $selected_bundles_json = $this->db->escape(json_encode($selected_bundles));
        $bundle_options_json   = $this->db->escape(json_encode($bundle_options));

        // نتحقق هل العنصر موجود مسبقًا بنفس المعطيات
        $check_sql = "
            SELECT COUNT(*) AS total
            FROM " . DB_PREFIX . "cart
            WHERE api_id      = '" . (int)$api_id . "'
              AND customer_id = '" . (int)$customer_id . "'
              AND session_id  = '" . $session_id . "'
              AND product_id  = '" . (int)$product_id . "'
              AND unit_id     = '" . (int)$unit_id . "'
              AND `option`    = '" . $option_json . "'
              AND `is_free`   = '" . (int)$is_free_int . "'
              $bundle_sql
              $pqd_sql
              $group_sql
        ";
        $check_query = $this->db->query($check_sql);
        $exists = ($check_query->row['total'] > 0);

        // إعداد حقول الـSET
        $price_sql = ($price !== null)
            ? ", price = '" . (float)$price . "'"
            : ", price = NULL";

        $bundle_set = ($bundle_id === null)
            ? ", bundle_id = NULL"
            : ", bundle_id = '" . (int)$bundle_id . "'";

        $pqd_set = ($pqd_id === null)
            ? ", product_quantity_discount_id = NULL"
            : ", product_quantity_discount_id = '" . (int)$pqd_id . "'";

        $group_set = ($group_id === null)
            ? ", group_id = NULL"
            : ", group_id = '" . $this->db->escape($group_id) . "'";

        $selected_bundles_set = ", selected_bundles = '" . $selected_bundles_json . "'";
        $bundle_options_set   = ", bundle_options   = '" . $bundle_options_json . "'";

        if (!$exists) {
            // INSERT
            $sql = "
                INSERT INTO " . DB_PREFIX . "cart
                SET api_id      = '" . (int)$api_id . "',
                    customer_id = '" . (int)$customer_id . "',
                    session_id  = '" . $session_id . "',
                    product_id  = '" . (int)$product_id . "',
                    unit_id     = '" . (int)$unit_id . "',
                    `option`    = '" . $option_json . "',
                    quantity    = '" . (int)$quantity . "',
                    `is_free`   = '" . (int)$is_free_int . "',
                    date_added  = NOW()
                    $price_sql
                    $bundle_set
                    $pqd_set
                    $group_set
                    $selected_bundles_set
                    $bundle_options_set
            ";
            $this->db->query($sql);

        } else {
            // UPDATE => نجمع الكمية أو نحدث الحقول
            $sql = "
                UPDATE " . DB_PREFIX . "cart
                SET quantity = (quantity + " . (int)$quantity . ")
                    $price_sql,
                    `is_free` = '" . (int)$is_free_int . "'
                    $bundle_set
                    $pqd_set
                    $group_set
                    $selected_bundles_set
                    $bundle_options_set
                WHERE api_id      = '" . (int)$api_id . "'
                  AND customer_id = '" . (int)$customer_id . "'
                  AND session_id  = '" . $session_id . "'
                  AND product_id  = '" . (int)$product_id . "'
                  AND unit_id     = '" . (int)$unit_id . "'
                  AND `option`    = '" . $option_json . "'
                  AND `is_free`   = '" . (int)$is_free_int . "'
                  $bundle_sql
                  $pqd_sql
                  $group_sql
            ";
            $this->db->query($sql);
        }
    }

    /**
     * تعديل كمية منتج (cart_id) 
     */
    public function update($cart_id, $quantity, $price = null) {
        $api_id      = isset($this->session->data['api_id']) ? (int)$this->session->data['api_id'] : 0;
        $customer_id = (int)$this->customer->getId();
        $session_id  = $this->db->escape($this->session->getId());

        // لو لديك price جديد
        $price_sql = ($price !== null)
            ? ", price = '" . (float)$price . "'"
            : "";

        $this->db->query("
            UPDATE " . DB_PREFIX . "cart
            SET quantity = '" . (int)$quantity . "'
                $price_sql
            WHERE cart_id     = '" . (int)$cart_id . "'
              AND api_id      = '" . $api_id . "'
              AND customer_id = '" . $customer_id . "'
              AND session_id  = '" . $session_id . "'
        ");
    }

    /**
     * إزالة عنصر محدد من السلة (cart_id)
     */
    public function remove($cart_id) {
        $api_id      = isset($this->session->data['api_id']) ? (int)$this->session->data['api_id'] : 0;
        $customer_id = (int)$this->customer->getId();
        $session_id  = $this->db->escape($this->session->getId());

        $this->db->query("
            DELETE FROM " . DB_PREFIX . "cart
            WHERE cart_id     = '" . (int)$cart_id . "'
              AND api_id      = '" . $api_id . "'
              AND customer_id = '" . $customer_id . "'
              AND session_id  = '" . $session_id . "'
        ");
    }

    /**
     * إزالة كل المنتجات (سلة كاملة)
     */
    public function clear() {
        $api_id      = isset($this->session->data['api_id']) ? (int)$this->session->data['api_id'] : 0;
        $customer_id = (int)$this->customer->getId();
        $session_id  = $this->db->escape($this->session->getId());

        $this->db->query("
            DELETE FROM " . DB_PREFIX . "cart
            WHERE api_id      = '" . (int)$api_id . "'
              AND customer_id = '" . $customer_id . "'
              AND session_id  = '" . $session_id . "'
        ");
    }

    /**
     * جلب وزن كل المنتجات في السلة
     */
    public function getWeight() {
        $weight   = 0.0;
        $products = $this->getProducts();
        foreach ($products as $product) {
            if (!empty($product['shipping'])) {
                // حول الوزن للوحدة الأساسية في المتجر
                $converted = $this->weight->convert(
                    $product['weight'],
                    $product['weight_class_id'],
                    $this->config->get('config_weight_class_id')
                );
                $weight += $converted;
            }
        }
        return $weight;
    }

    /**
     * المجموع قبل الضريبة
     */
    public function getSubTotal() {
        $total   = 0.0;
        $products= $this->getProducts();
        foreach ($products as $product) {
            // هذا total يمثل السعر الصافي * الكمية
            $total += $product['total'];
        }
        return $total;
    }

    /**
     * جلب ضرائب كل المنتجات
     */
    public function getTaxes() {
        $tax_data = [];
        $products = $this->getProducts();

        foreach ($products as $product) {
            if (!empty($product['tax_class_id'])) {
                $tax_rates = $this->tax->getRates($product['price'], $product['tax_class_id']);
                foreach ($tax_rates as $tax_rate) {
                    $tid = $tax_rate['tax_rate_id'];
                    $amt = $tax_rate['amount'] * $product['quantity'];

                    if (!isset($tax_data[$tid])) {
                        $tax_data[$tid] = $amt;
                    } else {
                        $tax_data[$tid] += $amt;
                    }
                }
            }
        }

        return $tax_data;
    }

    /**
     * المجموع الكلي بعد الضريبة (لو config_tax=1)
     */
    public function getTotal() {
        $total   = 0.0;
        $products= $this->getProducts();
        foreach ($products as $product) {
            // نضيف الضريبة لو config_tax=1
            $calc = $this->tax->calculate(
                $product['price'],
                $product['tax_class_id'],
                1
            ) * $product['quantity'];

            $total += $calc;
        }
        return $total;
    }

    /**
     * إجمالي عدد القطع في السلة
     */
    public function countProducts() {
        $count = 0;
        $products = $this->getProducts();
        foreach ($products as $product) {
            $count += $product['quantity'];
        }
        return $count;
    }

    /**
     * هل هناك منتجات في السلة؟
     */
    public function hasProducts() {
        return (count($this->getProducts()) > 0);
    }

    /**
     * هل كل المنتجات متوفرة؟
     */
    public function hasStock() {
        foreach ($this->getProducts() as $product) {
            if (!$product['stock']) {
                return false;
            }
        }
        return true;
    }

    /**
     * هل يوجد منتج يحتاج شحن؟
     */
    public function hasShipping() {
        return true;
    }

    /**
     * دالة مساعدة لجلب صف محدد من السلة (دون حسابات)
     */
    public function chekGroup($cart_id) {
        $api_id      = isset($this->session->data['api_id']) ? (int)$this->session->data['api_id'] : 0;
        $customer_id = (int)$this->customer->getId();
        $session_id  = $this->db->escape($this->session->getId());

        $q = $this->db->query("
            SELECT *
            FROM " . DB_PREFIX . "cart
            WHERE api_id      = '" . $api_id . "'
              AND customer_id = '" . $customer_id . "'
              AND session_id  = '" . $session_id . "'
              AND cart_id     = '" . (int)$cart_id . "'
            LIMIT 1
        ");
        return $q->num_rows ? $q->row : null;
    }

    /**
     * حذف مجموعة كاملة من المنتجات بنفس group_id
     */
    public function removeByGroup($group_id) {
        $api_id      = isset($this->session->data['api_id']) ? (int)$this->session->data['api_id'] : 0;
        $customer_id = (int)$this->customer->getId();
        $session_id  = $this->db->escape($this->session->getId());
        $escaped_gid = $this->db->escape($group_id);

        $this->db->query("
            DELETE FROM " . DB_PREFIX . "cart
            WHERE group_id    = '" . $escaped_gid . "'
              AND api_id      = '" . $api_id . "'
              AND customer_id = '" . $customer_id . "'
              AND session_id  = '" . $session_id . "'
        ");
    }
}
