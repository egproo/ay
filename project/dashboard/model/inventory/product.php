<?php
/**
 * نموذج إدارة المنتجات المتقدمة في المخزون (Advanced Product Management Model)
 *
 * الهدف: توفير إدارة منتجات متفوقة على Odoo وWooCommerce وSAP
 * الميزات: WAC متطور، تسعير 5 مستويات، خيارات مرتبطة بالوحدات، باركود متقدم
 * التكامل: مع المحاسبة والمشتريات والمبيعات والمخزون
 *
 * @author ERP Team
 * @version 1.0
 * @since 2024
 */

class ModelInventoryProduct extends Model {

    /**
     * إضافة منتج جديد
     */
    public function addProduct($data) {
        // إدراج المنتج الأساسي
        $this->db->query("
            INSERT INTO " . DB_PREFIX . "cod_product SET
            model = '" . $this->db->escape($data['model']) . "',
            sku = '" . $this->db->escape($data['sku']) . "',
            upc = '" . $this->db->escape($data['upc']) . "',
            ean = '" . $this->db->escape($data['ean']) . "',
            jan = '" . $this->db->escape($data['jan']) . "',
            isbn = '" . $this->db->escape($data['isbn']) . "',
            mpn = '" . $this->db->escape($data['mpn']) . "',
            location = '" . $this->db->escape($data['location']) . "',
            quantity = '" . (int)$data['quantity'] . "',
            minimum = '" . (int)$data['minimum'] . "',
            subtract = '" . (int)$data['subtract'] . "',
            stock_status_id = '" . (int)$data['stock_status_id'] . "',
            date_available = '" . $this->db->escape($data['date_available']) . "',
            manufacturer_id = '" . (int)$data['manufacturer_id'] . "',
            shipping = '" . (int)$data['shipping'] . "',
            price = '" . (float)$data['price'] . "',
            points = '" . (int)$data['points'] . "',
            tax_class_id = '" . (int)$data['tax_class_id'] . "',
            weight = '" . (float)$data['weight'] . "',
            weight_class_id = '" . (int)$data['weight_class_id'] . "',
            length = '" . (float)$data['length'] . "',
            width = '" . (float)$data['width'] . "',
            height = '" . (float)$data['height'] . "',
            length_class_id = '" . (int)$data['length_class_id'] . "',
            status = '" . (int)$data['status'] . "',
            sort_order = '" . (int)$data['sort_order'] . "',
            date_added = NOW(),
            date_modified = NOW()
        ");

        $product_id = $this->db->getLastId();

        // إدراج أوصاف المنتج
        if (isset($data['product_description'])) {
            foreach ($data['product_description'] as $language_id => $value) {
                $this->db->query("
                    INSERT INTO " . DB_PREFIX . "product_description SET
                    product_id = '" . (int)$product_id . "',
                    language_id = '" . (int)$language_id . "',
                    name = '" . $this->db->escape($value['name']) . "',
                    description = '" . $this->db->escape($value['description']) . "',
                    tag = '" . $this->db->escape($value['tag']) . "',
                    meta_title = '" . $this->db->escape($value['meta_title']) . "',
                    meta_description = '" . $this->db->escape($value['meta_description']) . "',
                    meta_keyword = '" . $this->db->escape($value['meta_keyword']) . "'
                ");
            }
        }

        // إدراج تصنيفات المنتج
        if (isset($data['product_category'])) {
            foreach ($data['product_category'] as $category_id) {
                $this->db->query("
                    INSERT INTO " . DB_PREFIX . "product_to_category SET
                    product_id = '" . (int)$product_id . "',
                    category_id = '" . (int)$category_id . "'
                ");
            }
        }

        // إدراج وحدات المنتج المتطورة
        if (isset($data['product_units'])) {
            foreach ($data['product_units'] as $unit_data) {
                $this->addProductUnit($product_id, $unit_data);
            }
        }

        // إدراج التسعير المتطور (5 مستويات)
        if (isset($data['product_pricing'])) {
            foreach ($data['product_pricing'] as $pricing_data) {
                $this->addProductPricing($product_id, $pricing_data);
            }
        }

        // إدراج الباركود المتقدم
        if (isset($data['product_barcodes'])) {
            foreach ($data['product_barcodes'] as $barcode_data) {
                $this->addProductBarcode($product_id, $barcode_data);
            }
        }

        // إدراج خيارات المنتج المرتبطة بالوحدات
        if (isset($data['product_options'])) {
            foreach ($data['product_options'] as $option_data) {
                $this->addProductOption($product_id, $option_data);
            }
        }

        // إدراج الباقات والخصومات
        if (isset($data['product_bundles'])) {
            foreach ($data['product_bundles'] as $bundle_data) {
                $this->addProductBundle($product_id, $bundle_data);
            }
        }

        if (isset($data['product_discounts'])) {
            foreach ($data['product_discounts'] as $discount_data) {
                $this->addProductDiscount($product_id, $discount_data);
            }
        }

        // تحديث المخزون في الفروع
        $this->updateProductInventory($product_id, $data);

        // إنشاء قيد محاسبي للمخزون الأولي (إذا كان هناك كمية)
        if (isset($data['quantity']) && $data['quantity'] > 0 && isset($data['price']) && $data['price'] > 0) {
            $this->createInitialInventoryEntry($product_id, $data);
        }

        return $product_id;
    }

    /**
     * تعديل منتج موجود
     */
    public function editProduct($product_id, $data) {
        // تحديث المنتج الأساسي
        $this->db->query("
            UPDATE " . DB_PREFIX . "cod_product SET
            model = '" . $this->db->escape($data['model']) . "',
            sku = '" . $this->db->escape($data['sku']) . "',
            upc = '" . $this->db->escape($data['upc']) . "',
            ean = '" . $this->db->escape($data['ean']) . "',
            jan = '" . $this->db->escape($data['jan']) . "',
            isbn = '" . $this->db->escape($data['isbn']) . "',
            mpn = '" . $this->db->escape($data['mpn']) . "',
            location = '" . $this->db->escape($data['location']) . "',
            minimum = '" . (int)$data['minimum'] . "',
            subtract = '" . (int)$data['subtract'] . "',
            stock_status_id = '" . (int)$data['stock_status_id'] . "',
            date_available = '" . $this->db->escape($data['date_available']) . "',
            manufacturer_id = '" . (int)$data['manufacturer_id'] . "',
            shipping = '" . (int)$data['shipping'] . "',
            points = '" . (int)$data['points'] . "',
            tax_class_id = '" . (int)$data['tax_class_id'] . "',
            weight = '" . (float)$data['weight'] . "',
            weight_class_id = '" . (int)$data['weight_class_id'] . "',
            length = '" . (float)$data['length'] . "',
            width = '" . (float)$data['width'] . "',
            height = '" . (float)$data['height'] . "',
            length_class_id = '" . (int)$data['length_class_id'] . "',
            status = '" . (int)$data['status'] . "',
            sort_order = '" . (int)$data['sort_order'] . "',
            date_modified = NOW()
            WHERE product_id = '" . (int)$product_id . "'
        ");

        // حذف وإعادة إدراج الأوصاف
        $this->db->query("DELETE FROM " . DB_PREFIX . "product_description WHERE product_id = '" . (int)$product_id . "'");

        if (isset($data['product_description'])) {
            foreach ($data['product_description'] as $language_id => $value) {
                $this->db->query("
                    INSERT INTO " . DB_PREFIX . "product_description SET
                    product_id = '" . (int)$product_id . "',
                    language_id = '" . (int)$language_id . "',
                    name = '" . $this->db->escape($value['name']) . "',
                    description = '" . $this->db->escape($value['description']) . "',
                    tag = '" . $this->db->escape($value['tag']) . "',
                    meta_title = '" . $this->db->escape($value['meta_title']) . "',
                    meta_description = '" . $this->db->escape($value['meta_description']) . "',
                    meta_keyword = '" . $this->db->escape($value['meta_keyword']) . "'
                ");
            }
        }

        // تحديث التصنيفات
        $this->db->query("DELETE FROM " . DB_PREFIX . "product_to_category WHERE product_id = '" . (int)$product_id . "'");

        if (isset($data['product_category'])) {
            foreach ($data['product_category'] as $category_id) {
                $this->db->query("
                    INSERT INTO " . DB_PREFIX . "product_to_category SET
                    product_id = '" . (int)$product_id . "',
                    category_id = '" . (int)$category_id . "'
                ");
            }
        }

        // تحديث الوحدات
        $this->db->query("DELETE FROM " . DB_PREFIX . "cod_product_unit WHERE product_id = '" . (int)$product_id . "'");

        if (isset($data['product_units'])) {
            foreach ($data['product_units'] as $unit_data) {
                $this->addProductUnit($product_id, $unit_data);
            }
        }

        // تحديث التسعير
        $this->db->query("DELETE FROM " . DB_PREFIX . "cod_product_pricing WHERE product_id = '" . (int)$product_id . "'");

        if (isset($data['product_pricing'])) {
            foreach ($data['product_pricing'] as $pricing_data) {
                $this->addProductPricing($product_id, $pricing_data);
            }
        }

        // تحديث الباركود
        $this->db->query("DELETE FROM " . DB_PREFIX . "cod_product_barcode WHERE product_id = '" . (int)$product_id . "'");

        if (isset($data['product_barcodes'])) {
            foreach ($data['product_barcodes'] as $barcode_data) {
                $this->addProductBarcode($product_id, $barcode_data);
            }
        }

        // تحديث الخيارات
        $this->db->query("DELETE FROM " . DB_PREFIX . "product_option WHERE product_id = '" . (int)$product_id . "'");
        $this->db->query("DELETE FROM " . DB_PREFIX . "product_option_value WHERE product_option_id IN (SELECT product_option_id FROM " . DB_PREFIX . "product_option WHERE product_id = '" . (int)$product_id . "')");

        if (isset($data['product_options'])) {
            foreach ($data['product_options'] as $option_data) {
                $this->addProductOption($product_id, $option_data);
            }
        }

        // تحديث الباقات والخصومات
        $this->db->query("DELETE FROM " . DB_PREFIX . "cod_product_bundle WHERE product_id = '" . (int)$product_id . "'");
        $this->db->query("DELETE FROM " . DB_PREFIX . "cod_product_discount WHERE product_id = '" . (int)$product_id . "'");

        if (isset($data['product_bundles'])) {
            foreach ($data['product_bundles'] as $bundle_data) {
                $this->addProductBundle($product_id, $bundle_data);
            }
        }

        if (isset($data['product_discounts'])) {
            foreach ($data['product_discounts'] as $discount_data) {
                $this->addProductDiscount($product_id, $discount_data);
            }
        }
    }

    /**
     * حذف منتج
     */
    public function deleteProduct($product_id) {
        // حذف جميع البيانات المرتبطة
        $this->db->query("DELETE FROM " . DB_PREFIX . "cod_product WHERE product_id = '" . (int)$product_id . "'");
        $this->db->query("DELETE FROM " . DB_PREFIX . "product_description WHERE product_id = '" . (int)$product_id . "'");
        $this->db->query("DELETE FROM " . DB_PREFIX . "product_to_category WHERE product_id = '" . (int)$product_id . "'");
        $this->db->query("DELETE FROM " . DB_PREFIX . "cod_product_unit WHERE product_id = '" . (int)$product_id . "'");
        $this->db->query("DELETE FROM " . DB_PREFIX . "cod_product_pricing WHERE product_id = '" . (int)$product_id . "'");
        $this->db->query("DELETE FROM " . DB_PREFIX . "cod_product_barcode WHERE product_id = '" . (int)$product_id . "'");
        $this->db->query("DELETE FROM " . DB_PREFIX . "product_option WHERE product_id = '" . (int)$product_id . "'");
        $this->db->query("DELETE FROM " . DB_PREFIX . "cod_product_bundle WHERE product_id = '" . (int)$product_id . "'");
        $this->db->query("DELETE FROM " . DB_PREFIX . "cod_product_discount WHERE product_id = '" . (int)$product_id . "'");
        $this->db->query("DELETE FROM " . DB_PREFIX . "cod_product_inventory WHERE product_id = '" . (int)$product_id . "'");
        $this->db->query("DELETE FROM " . DB_PREFIX . "cod_product_movement WHERE product_id = '" . (int)$product_id . "'");
    }

    /**
     * الحصول على منتج واحد
     */
    public function getProduct($product_id) {
        $query = $this->db->query("
            SELECT DISTINCT *,
            (SELECT keyword FROM " . DB_PREFIX . "seo_url WHERE query = 'product_id=" . (int)$product_id . "' AND store_id = '0' AND language_id = '" . (int)$this->config->get('config_language_id') . "' LIMIT 1) AS keyword
            FROM " . DB_PREFIX . "cod_product p
            LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id)
            WHERE p.product_id = '" . (int)$product_id . "'
            AND pd.language_id = '" . (int)$this->config->get('config_language_id') . "'
        ");

        return $query->row;
    }

    /**
     * الحصول على قائمة المنتجات مع الفلاتر
     */
    public function getProducts($data = array()) {
        $sql = "
            SELECT p.product_id,
            (SELECT pd.name FROM " . DB_PREFIX . "product_description pd WHERE pd.product_id = p.product_id AND pd.language_id = '" . (int)$this->config->get('config_language_id') . "' LIMIT 1) AS name,
            p.model, p.sku, p.upc, p.ean, p.image, p.price, p.quantity, p.status, p.sort_order,
            (SELECT SUM(pi.quantity) FROM " . DB_PREFIX . "cod_product_inventory pi WHERE pi.product_id = p.product_id) AS total_quantity,
            p.average_cost, p.date_added, p.date_modified
            FROM " . DB_PREFIX . "cod_product p
        ";

        $where = array();

        if (!empty($data['filter_name'])) {
            $where[] = "(SELECT pd.name FROM " . DB_PREFIX . "product_description pd WHERE pd.product_id = p.product_id AND pd.language_id = '" . (int)$this->config->get('config_language_id') . "' LIMIT 1) LIKE '%" . $this->db->escape($data['filter_name']) . "%'";
        }

        if (!empty($data['filter_model'])) {
            $where[] = "p.model LIKE '%" . $this->db->escape($data['filter_model']) . "%'";
        }

        if (isset($data['filter_status']) && $data['filter_status'] !== '') {
            $where[] = "p.status = '" . (int)$data['filter_status'] . "'";
        }

        if ($where) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }

        $sort_data = array(
            'pd.name',
            'p.model',
            'p.price',
            'p.quantity',
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

    /**
     * الحصول على إجمالي عدد المنتجات
     */
    public function getTotalProducts($data = array()) {
        $sql = "SELECT COUNT(DISTINCT p.product_id) AS total FROM " . DB_PREFIX . "cod_product p";

        $where = array();

        if (!empty($data['filter_name'])) {
            $where[] = "(SELECT pd.name FROM " . DB_PREFIX . "product_description pd WHERE pd.product_id = p.product_id AND pd.language_id = '" . (int)$this->config->get('config_language_id') . "' LIMIT 1) LIKE '%" . $this->db->escape($data['filter_name']) . "%'";
        }

        if (!empty($data['filter_model'])) {
            $where[] = "p.model LIKE '%" . $this->db->escape($data['filter_model']) . "%'";
        }

        if (isset($data['filter_status']) && $data['filter_status'] !== '') {
            $where[] = "p.status = '" . (int)$data['filter_status'] . "'";
        }

        if ($where) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }

        $query = $this->db->query($sql);

        return $query->row['total'];
    }

    /**
     * إضافة وحدة للمنتج
     */
    public function addProductUnit($product_id, $data) {
        $this->db->query("
            INSERT INTO " . DB_PREFIX . "cod_product_unit SET
            product_id = '" . (int)$product_id . "',
            unit_id = '" . (int)$data['unit_id'] . "',
            unit_type = '" . $this->db->escape($data['unit_type']) . "',
            conversion_factor = '" . (float)$data['conversion_factor'] . "',
            is_base_unit = '" . (int)$data['is_base_unit'] . "',
            sort_order = '" . (int)$data['sort_order'] . "'
        ");
    }

    /**
     * إضافة تسعير للمنتج (5 مستويات)
     */
    public function addProductPricing($product_id, $data) {
        $this->db->query("
            INSERT INTO " . DB_PREFIX . "cod_product_pricing SET
            product_id = '" . (int)$product_id . "',
            unit_id = '" . (int)$data['unit_id'] . "',
            cost_price = '" . (float)$data['cost_price'] . "',
            base_price = '" . (float)$data['base_price'] . "',
            special_price = '" . (float)$data['special_price'] . "',
            wholesale_price = '" . (float)$data['wholesale_price'] . "',
            half_wholesale_price = '" . (float)$data['half_wholesale_price'] . "',
            custom_price = '" . (float)$data['custom_price'] . "',
            profit_margin = '" . (float)$data['profit_margin'] . "',
            date_start = '" . $this->db->escape($data['date_start']) . "',
            date_end = '" . $this->db->escape($data['date_end']) . "'
        ");
    }

    /**
     * إضافة باركود للمنتج
     */
    public function addProductBarcode($product_id, $data) {
        $this->db->query("
            INSERT INTO " . DB_PREFIX . "cod_product_barcode SET
            product_id = '" . (int)$product_id . "',
            barcode = '" . $this->db->escape($data['barcode']) . "',
            barcode_type = '" . $this->db->escape($data['barcode_type']) . "',
            unit_id = '" . (int)$data['unit_id'] . "',
            option_id = '" . (int)$data['option_id'] . "',
            option_value_id = '" . (int)$data['option_value_id'] . "',
            is_primary = '" . (int)$data['is_primary'] . "'
        ");
    }

    /**
     * إضافة خيار للمنتج (مرتبط بالوحدات)
     */
    public function addProductOption($product_id, $data) {
        $this->db->query("
            INSERT INTO " . DB_PREFIX . "product_option SET
            product_id = '" . (int)$product_id . "',
            option_id = '" . (int)$data['option_id'] . "',
            unit_id = '" . (int)$data['unit_id'] . "',
            value = '" . $this->db->escape($data['value']) . "',
            required = '" . (int)$data['required'] . "'
        ");

        $product_option_id = $this->db->getLastId();

        if (isset($data['product_option_value'])) {
            foreach ($data['product_option_value'] as $option_value) {
                $this->db->query("
                    INSERT INTO " . DB_PREFIX . "product_option_value SET
                    product_option_id = '" . (int)$product_option_id . "',
                    product_id = '" . (int)$product_id . "',
                    option_id = '" . (int)$data['option_id'] . "',
                    option_value_id = '" . (int)$option_value['option_value_id'] . "',
                    quantity = '" . (int)$option_value['quantity'] . "',
                    subtract = '" . (int)$option_value['subtract'] . "',
                    price = '" . (float)$option_value['price'] . "',
                    price_prefix = '" . $this->db->escape($option_value['price_prefix']) . "',
                    points = '" . (int)$option_value['points'] . "',
                    points_prefix = '" . $this->db->escape($option_value['points_prefix']) . "',
                    weight = '" . (float)$option_value['weight'] . "',
                    weight_prefix = '" . $this->db->escape($option_value['weight_prefix']) . "'
                ");
            }
        }
    }

    /**
     * إضافة باقة للمنتج
     */
    public function addProductBundle($product_id, $data) {
        $this->db->query("
            INSERT INTO " . DB_PREFIX . "cod_product_bundle SET
            product_id = '" . (int)$product_id . "',
            name = '" . $this->db->escape($data['name']) . "',
            discount_type = '" . $this->db->escape($data['discount_type']) . "',
            discount_value = '" . (float)$data['discount_value'] . "',
            status = '" . (int)$data['status'] . "',
            date_start = '" . $this->db->escape($data['date_start']) . "',
            date_end = '" . $this->db->escape($data['date_end']) . "'
        ");

        $bundle_id = $this->db->getLastId();

        if (isset($data['bundle_items'])) {
            foreach ($data['bundle_items'] as $item) {
                $this->db->query("
                    INSERT INTO " . DB_PREFIX . "cod_product_bundle_item SET
                    bundle_id = '" . (int)$bundle_id . "',
                    product_id = '" . (int)$item['product_id'] . "',
                    quantity = '" . (int)$item['quantity'] . "',
                    unit_id = '" . (int)$item['unit_id'] . "',
                    is_free = '" . (int)$item['is_free'] . "'
                ");
            }
        }
    }

    /**
     * إضافة خصم للمنتج
     */
    public function addProductDiscount($product_id, $data) {
        $this->db->query("
            INSERT INTO " . DB_PREFIX . "cod_product_discount SET
            product_id = '" . (int)$product_id . "',
            name = '" . $this->db->escape($data['name']) . "',
            type = '" . $this->db->escape($data['type']) . "',
            buy_quantity = '" . (int)$data['buy_quantity'] . "',
            get_quantity = '" . (int)$data['get_quantity'] . "',
            discount_type = '" . $this->db->escape($data['discount_type']) . "',
            discount_value = '" . (float)$data['discount_value'] . "',
            unit_id = '" . (int)$data['unit_id'] . "',
            status = '" . (int)$data['status'] . "',
            date_start = '" . $this->db->escape($data['date_start']) . "',
            date_end = '" . $this->db->escape($data['date_end']) . "',
            notes = '" . $this->db->escape($data['notes']) . "'
        ");
    }

    /**
     * تحديث مخزون المنتج في الفروع
     */
    public function updateProductInventory($product_id, $data) {
        if (isset($data['branch_inventory'])) {
            foreach ($data['branch_inventory'] as $branch_id => $inventory_data) {
                $this->db->query("
                    INSERT INTO " . DB_PREFIX . "cod_product_inventory SET
                    product_id = '" . (int)$product_id . "',
                    branch_id = '" . (int)$branch_id . "',
                    quantity = '" . (float)$inventory_data['quantity'] . "',
                    reserved_quantity = '0',
                    location = '" . $this->db->escape($inventory_data['location']) . "',
                    last_updated = NOW()
                    ON DUPLICATE KEY UPDATE
                    quantity = '" . (float)$inventory_data['quantity'] . "',
                    location = '" . $this->db->escape($inventory_data['location']) . "',
                    last_updated = NOW()
                ");
            }
        }
    }

    /**
     * إنشاء قيد محاسبي للمخزون الأولي
     */
    public function createInitialInventoryEntry($product_id, $data) {
        $this->load->model('accounting/journal');

        $total_value = (float)$data['quantity'] * (float)$data['price'];

        $journal_data = array(
            'reference' => 'INV-INIT-' . $product_id,
            'description' => 'مخزون أولي للمنتج: ' . $data['product_description'][1]['name'],
            'date' => date('Y-m-d'),
            'entries' => array(
                array(
                    'account_id' => $this->config->get('inventory_asset_account'),
                    'debit' => $total_value,
                    'credit' => 0,
                    'description' => 'مخزون أولي'
                ),
                array(
                    'account_id' => $this->config->get('inventory_equity_account'),
                    'debit' => 0,
                    'credit' => $total_value,
                    'description' => 'رأس مال مخزون أولي'
                )
            )
        );

        $this->model_accounting_journal->addEntry($journal_data);
    }

    /**
     * توليد كود المنتج التلقائي
     */
    public function generateProductCode($category_id = 0, $manufacturer_id = 0) {
        $code = '';

        // بادئة القسم
        if ($category_id > 0) {
            $category_query = $this->db->query("
                SELECT code_prefix FROM " . DB_PREFIX . "category
                WHERE category_id = '" . (int)$category_id . "'
            ");

            if ($category_query->num_rows) {
                $code .= $category_query->row['code_prefix'];
            } else {
                $code .= 'CAT';
            }
        } else {
            $code .= 'PRD';
        }

        // بادئة العلامة التجارية
        if ($manufacturer_id > 0) {
            $manufacturer_query = $this->db->query("
                SELECT code_prefix FROM " . DB_PREFIX . "manufacturer
                WHERE manufacturer_id = '" . (int)$manufacturer_id . "'
            ");

            if ($manufacturer_query->num_rows) {
                $code .= '-' . $manufacturer_query->row['code_prefix'];
            }
        }

        // الرقم التسلسلي
        $sequence_query = $this->db->query("
            SELECT MAX(CAST(SUBSTRING(model, LENGTH('" . $this->db->escape($code) . "') + 2) AS UNSIGNED)) as max_sequence
            FROM " . DB_PREFIX . "cod_product
            WHERE model LIKE '" . $this->db->escape($code) . "-%'
        ");

        $next_sequence = 1;
        if ($sequence_query->num_rows && $sequence_query->row['max_sequence']) {
            $next_sequence = (int)$sequence_query->row['max_sequence'] + 1;
        }

        $code .= '-' . str_pad($next_sequence, 4, '0', STR_PAD_LEFT);

        return $code;
    }

    /**
     * الحصول على أوصاف المنتج
     */
    public function getProductDescriptions($product_id) {
        $product_description_data = array();

        $query = $this->db->query("
            SELECT * FROM " . DB_PREFIX . "product_description
            WHERE product_id = '" . (int)$product_id . "'
        ");

        foreach ($query->rows as $result) {
            $product_description_data[$result['language_id']] = array(
                'name'             => $result['name'],
                'description'      => $result['description'],
                'tag'              => $result['tag'],
                'meta_title'       => $result['meta_title'],
                'meta_description' => $result['meta_description'],
                'meta_keyword'     => $result['meta_keyword']
            );
        }

        return $product_description_data;
    }

    /**
     * الحصول على تصنيفات المنتج
     */
    public function getProductCategories($product_id) {
        $product_category_data = array();

        $query = $this->db->query("
            SELECT * FROM " . DB_PREFIX . "product_to_category
            WHERE product_id = '" . (int)$product_id . "'
        ");

        foreach ($query->rows as $result) {
            $product_category_data[] = $result['category_id'];
        }

        return $product_category_data;
    }

    /**
     * الحصول على وحدات المنتج
     */
    public function getProductUnits($product_id) {
        $query = $this->db->query("
            SELECT pu.*, u.name as unit_name
            FROM " . DB_PREFIX . "cod_product_unit pu
            LEFT JOIN " . DB_PREFIX . "cod_unit u ON (pu.unit_id = u.unit_id)
            WHERE pu.product_id = '" . (int)$product_id . "'
            ORDER BY pu.sort_order ASC
        ");

        return $query->rows;
    }

    /**
     * الحصول على تسعير المنتج
     */
    public function getProductPricing($product_id) {
        $query = $this->db->query("
            SELECT pp.*, u.name as unit_name
            FROM " . DB_PREFIX . "cod_product_pricing pp
            LEFT JOIN " . DB_PREFIX . "cod_unit u ON (pp.unit_id = u.unit_id)
            WHERE pp.product_id = '" . (int)$product_id . "'
        ");

        return $query->rows;
    }

    /**
     * الحصول على باركود المنتج
     */
    public function getProductBarcodes($product_id) {
        $query = $this->db->query("
            SELECT pb.*, u.name as unit_name, o.name as option_name, ov.name as option_value_name
            FROM " . DB_PREFIX . "cod_product_barcode pb
            LEFT JOIN " . DB_PREFIX . "cod_unit u ON (pb.unit_id = u.unit_id)
            LEFT JOIN " . DB_PREFIX . "option o ON (pb.option_id = o.option_id)
            LEFT JOIN " . DB_PREFIX . "option_value ov ON (pb.option_value_id = ov.option_value_id)
            WHERE pb.product_id = '" . (int)$product_id . "'
        ");

        return $query->rows;
    }

    /**
     * الحصول على خيارات المنتج
     */
    public function getProductOptions($product_id) {
        $product_option_data = array();

        $product_option_query = $this->db->query("
            SELECT * FROM " . DB_PREFIX . "product_option po
            LEFT JOIN `" . DB_PREFIX . "option` o ON (po.option_id = o.option_id)
            LEFT JOIN " . DB_PREFIX . "option_description od ON (o.option_id = od.option_id)
            WHERE po.product_id = '" . (int)$product_id . "'
            AND od.language_id = '" . (int)$this->config->get('config_language_id') . "'
        ");

        foreach ($product_option_query->rows as $product_option) {
            $product_option_value_data = array();

            $product_option_value_query = $this->db->query("
                SELECT * FROM " . DB_PREFIX . "product_option_value pov
                LEFT JOIN " . DB_PREFIX . "option_value ov ON (pov.option_value_id = ov.option_value_id)
                LEFT JOIN " . DB_PREFIX . "option_value_description ovd ON (ov.option_value_id = ovd.option_value_id)
                WHERE pov.product_id = '" . (int)$product_id . "'
                AND pov.product_option_id = '" . (int)$product_option['product_option_id'] . "'
                AND ovd.language_id = '" . (int)$this->config->get('config_language_id') . "'
            ");

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
                    'points'                  => $product_option_value['points'],
                    'points_prefix'           => $product_option_value['points_prefix'],
                    'weight'                  => $product_option_value['weight'],
                    'weight_prefix'           => $product_option_value['weight_prefix']
                );
            }

            $product_option_data[] = array(
                'product_option_id'    => $product_option['product_option_id'],
                'product_option_value' => $product_option_value_data,
                'option_id'            => $product_option['option_id'],
                'name'                 => $product_option['name'],
                'type'                 => $product_option['type'],
                'unit_id'              => $product_option['unit_id'],
                'value'                => $product_option['value'],
                'required'             => $product_option['required']
            );
        }

        return $product_option_data;
    }

    /**
     * الحصول على باقات المنتج
     */
    public function getProductBundles($product_id) {
        $query = $this->db->query("
            SELECT * FROM " . DB_PREFIX . "cod_product_bundle
            WHERE product_id = '" . (int)$product_id . "'
        ");

        $bundles = array();
        foreach ($query->rows as $bundle) {
            $items_query = $this->db->query("
                SELECT pbi.*, pd.name as product_name, u.name as unit_name
                FROM " . DB_PREFIX . "cod_product_bundle_item pbi
                LEFT JOIN " . DB_PREFIX . "product_description pd ON (pbi.product_id = pd.product_id)
                LEFT JOIN " . DB_PREFIX . "cod_unit u ON (pbi.unit_id = u.unit_id)
                WHERE pbi.bundle_id = '" . (int)$bundle['bundle_id'] . "'
                AND pd.language_id = '" . (int)$this->config->get('config_language_id') . "'
            ");

            $bundle['items'] = $items_query->rows;
            $bundles[] = $bundle;
        }

        return $bundles;
    }

    /**
     * الحصول على خصومات المنتج
     */
    public function getProductDiscounts($product_id) {
        $query = $this->db->query("
            SELECT pd.*, u.name as unit_name
            FROM " . DB_PREFIX . "cod_product_discount pd
            LEFT JOIN " . DB_PREFIX . "cod_unit u ON (pd.unit_id = u.unit_id)
            WHERE pd.product_id = '" . (int)$product_id . "'
        ");

        return $query->rows;
    }
}
