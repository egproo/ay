<?php
/**
 * نموذج إدارة المنتجات المتطورة (Advanced Product Management Model) - الجزء الأول
 *
 * الهدف: نقل إدارة المنتجات من الكتالوج إلى المخزون مع ميزات متقدمة
 * الميزات: ترميز ذكي، 5 مستويات تسعير، خيارات مرتبطة بالوحدات، باركود متعدد
 * التكامل: مع المخزون والوحدات والباركود والتسعير والخيارات
 *
 * @author ERP Team
 * @version 1.0
 * @since 2024
 */

class ModelInventoryProductManagement extends Model {

    /**
     * الحصول على المنتجات مع فلاتر متقدمة
     */
    public function getProducts($data = array()) {
        $sql = "
            SELECT
                p.product_id,
                pd.name,
                p.model,
                p.sku,
                p.upc,
                p.ean,
                p.jan,
                p.isbn,
                p.mpn,
                p.location,
                p.quantity,
                p.minimum,
                p.subtract,
                p.stock_status_id,
                ss.name as stock_status,
                p.image,
                p.manufacturer_id,
                m.name as manufacturer,
                p.shipping,
                p.price,
                p.points,
                p.tax_class_id,
                tc.title as tax_class,
                p.date_available,
                p.weight,
                p.weight_class_id,
                wc.title as weight_class,
                p.length,
                p.width,
                p.height,
                p.length_class_id,
                lc.title as length_class,
                p.status,
                p.sort_order,
                p.date_added,
                p.date_modified,

                -- معلومات المخزون المتقدمة
                pi.branch_id,
                pi.available_quantity,
                pi.reserved_quantity,
                pi.on_order_quantity,
                pi.reorder_level,
                pi.max_stock_level,
                pi.avg_cost,
                pi.last_cost,
                pi.standard_cost,

                -- معلومات التسعير المتقدمة
                pp.basic_price,
                pp.offer_price,
                pp.wholesale_price,
                pp.semi_wholesale_price,
                pp.special_price,
                pp.pos_price,
                pp.online_price,

                -- معلومات الوحدات
                pu.base_unit_id,
                bu.name as base_unit_name,
                bu.symbol as base_unit_symbol,

                -- إحصائيات الباركود
                (SELECT COUNT(*) FROM " . DB_PREFIX . "cod_product_barcode pb
                 WHERE pb.product_id = p.product_id AND pb.is_active = 1) as barcode_count,

                -- إحصائيات المبيعات
                (SELECT COALESCE(SUM(op.quantity), 0) FROM " . DB_PREFIX . "order_product op
                 LEFT JOIN " . DB_PREFIX . "order o ON (op.order_id = o.order_id)
                 WHERE op.product_id = p.product_id
                 AND o.order_status_id > 0
                 AND DATE(o.date_added) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)) as sales_30_days,

                -- آخر حركة مخزون
                (SELECT MAX(date_added) FROM " . DB_PREFIX . "cod_inventory_movement im
                 WHERE im.product_id = p.product_id) as last_movement_date,

                -- حالة المخزون المحسوبة
                CASE
                    WHEN pi.available_quantity <= 0 THEN 'out_of_stock'
                    WHEN pi.available_quantity <= pi.reorder_level THEN 'low_stock'
                    WHEN pi.available_quantity >= pi.max_stock_level THEN 'overstock'
                    ELSE 'in_stock'
                END as computed_stock_status,

                -- قيمة المخزون
                (pi.available_quantity * pi.avg_cost) as inventory_value,

                -- معدل دوران المخزون
                CASE
                    WHEN pi.avg_cost > 0 AND pi.available_quantity > 0 THEN
                        (SELECT COALESCE(SUM(op.quantity * op.price), 0) FROM " . DB_PREFIX . "order_product op
                         LEFT JOIN " . DB_PREFIX . "order o ON (op.order_id = o.order_id)
                         WHERE op.product_id = p.product_id
                         AND o.order_status_id > 0
                         AND DATE(o.date_added) >= DATE_SUB(CURDATE(), INTERVAL 365 DAY)) / (pi.available_quantity * pi.avg_cost)
                    ELSE 0
                END as inventory_turnover

            FROM " . DB_PREFIX . "cod_product p
            LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id)
            LEFT JOIN " . DB_PREFIX . "stock_status ss ON (p.stock_status_id = ss.stock_status_id)
            LEFT JOIN " . DB_PREFIX . "manufacturer m ON (p.manufacturer_id = m.manufacturer_id)
            LEFT JOIN " . DB_PREFIX . "tax_class tc ON (p.tax_class_id = tc.tax_class_id)
            LEFT JOIN " . DB_PREFIX . "weight_class_description wc ON (p.weight_class_id = wc.weight_class_id)
            LEFT JOIN " . DB_PREFIX . "length_class_description lc ON (p.length_class_id = lc.length_class_id)
            LEFT JOIN " . DB_PREFIX . "cod_product_inventory pi ON (p.product_id = pi.product_id)
            LEFT JOIN " . DB_PREFIX . "cod_product_pricing pp ON (p.product_id = pp.product_id)
            LEFT JOIN " . DB_PREFIX . "cod_product_unit pu ON (p.product_id = pu.product_id)
            LEFT JOIN " . DB_PREFIX . "cod_unit bu ON (pu.base_unit_id = bu.unit_id)
            LEFT JOIN " . DB_PREFIX . "cod_unit_description bud ON (bu.unit_id = bud.unit_id)
            WHERE pd.language_id = '" . (int)$this->config->get('config_language_id') . "'
            AND ss.language_id = '" . (int)$this->config->get('config_language_id') . "'
            AND wc.language_id = '" . (int)$this->config->get('config_language_id') . "'
            AND lc.language_id = '" . (int)$this->config->get('config_language_id') . "'
            AND bud.language_id = '" . (int)$this->config->get('config_language_id') . "'
        ";

        // تطبيق الفلاتر
        if (!empty($data['filter_name'])) {
            $sql .= " AND pd.name LIKE '%" . $this->db->escape($data['filter_name']) . "%'";
        }

        if (!empty($data['filter_model'])) {
            $sql .= " AND p.model LIKE '%" . $this->db->escape($data['filter_model']) . "%'";
        }

        if (!empty($data['filter_sku'])) {
            $sql .= " AND p.sku LIKE '%" . $this->db->escape($data['filter_sku']) . "%'";
        }

        if (!empty($data['filter_manufacturer_id'])) {
            $sql .= " AND p.manufacturer_id = '" . (int)$data['filter_manufacturer_id'] . "'";
        }

        if (!empty($data['filter_category_id'])) {
            $sql .= " AND p.product_id IN (SELECT product_id FROM " . DB_PREFIX . "product_to_category WHERE category_id = '" . (int)$data['filter_category_id'] . "')";
        }

        if (isset($data['filter_status']) && $data['filter_status'] !== '') {
            $sql .= " AND p.status = '" . (int)$data['filter_status'] . "'";
        }

        if (!empty($data['filter_stock_status'])) {
            $sql .= " AND computed_stock_status = '" . $this->db->escape($data['filter_stock_status']) . "'";
        }

        if (!empty($data['filter_price_from'])) {
            $sql .= " AND p.price >= '" . (float)$data['filter_price_from'] . "'";
        }

        if (!empty($data['filter_price_to'])) {
            $sql .= " AND p.price <= '" . (float)$data['filter_price_to'] . "'";
        }

        if (!empty($data['filter_quantity_from'])) {
            $sql .= " AND pi.available_quantity >= '" . (int)$data['filter_quantity_from'] . "'";
        }

        if (!empty($data['filter_quantity_to'])) {
            $sql .= " AND pi.available_quantity <= '" . (int)$data['filter_quantity_to'] . "'";
        }

        if (!empty($data['filter_date_from'])) {
            $sql .= " AND DATE(p.date_added) >= '" . $this->db->escape($data['filter_date_from']) . "'";
        }

        if (!empty($data['filter_date_to'])) {
            $sql .= " AND DATE(p.date_added) <= '" . $this->db->escape($data['filter_date_to']) . "'";
        }

        // ترتيب النتائج
        $sort_data = array(
            'pd.name',
            'p.model',
            'p.sku',
            'p.price',
            'pi.available_quantity',
            'p.status',
            'p.sort_order',
            'p.date_added',
            'p.date_modified',
            'inventory_value',
            'sales_30_days'
        );

        if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
            $sql .= " ORDER BY " . $data['sort'];
        } else {
            $sql .= " ORDER BY pd.name ASC";
        }

        if (isset($data['order']) && ($data['order'] == 'DESC')) {
            $sql .= " DESC";
        } else {
            $sql .= " ASC";
        }

        // تحديد عدد النتائج
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
        $sql = "
            SELECT COUNT(DISTINCT p.product_id) AS total
            FROM " . DB_PREFIX . "cod_product p
            LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id)
            LEFT JOIN " . DB_PREFIX . "cod_product_inventory pi ON (p.product_id = pi.product_id)
            WHERE pd.language_id = '" . (int)$this->config->get('config_language_id') . "'
        ";

        // تطبيق نفس الفلاتر
        if (!empty($data['filter_name'])) {
            $sql .= " AND pd.name LIKE '%" . $this->db->escape($data['filter_name']) . "%'";
        }

        if (!empty($data['filter_model'])) {
            $sql .= " AND p.model LIKE '%" . $this->db->escape($data['filter_model']) . "%'";
        }

        if (!empty($data['filter_sku'])) {
            $sql .= " AND p.sku LIKE '%" . $this->db->escape($data['filter_sku']) . "%'";
        }

        if (!empty($data['filter_manufacturer_id'])) {
            $sql .= " AND p.manufacturer_id = '" . (int)$data['filter_manufacturer_id'] . "'";
        }

        if (!empty($data['filter_category_id'])) {
            $sql .= " AND p.product_id IN (SELECT product_id FROM " . DB_PREFIX . "product_to_category WHERE category_id = '" . (int)$data['filter_category_id'] . "')";
        }

        if (isset($data['filter_status']) && $data['filter_status'] !== '') {
            $sql .= " AND p.status = '" . (int)$data['filter_status'] . "'";
        }

        $query = $this->db->query($sql);

        return $query->row['total'];
    }

    /**
     * الحصول على منتج محدد
     */
    public function getProduct($product_id) {
        $query = $this->db->query("
            SELECT
                p.*,
                pd.name,
                pd.description,
                pd.tag,
                pd.meta_title,
                pd.meta_description,
                pd.meta_keyword,
                pi.*,
                pp.*,
                pu.base_unit_id,
                bu.name as base_unit_name,
                bu.symbol as base_unit_symbol
            FROM " . DB_PREFIX . "cod_product p
            LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id)
            LEFT JOIN " . DB_PREFIX . "cod_product_inventory pi ON (p.product_id = pi.product_id)
            LEFT JOIN " . DB_PREFIX . "cod_product_pricing pp ON (p.product_id = pp.product_id)
            LEFT JOIN " . DB_PREFIX . "cod_product_unit pu ON (p.product_id = pu.product_id)
            LEFT JOIN " . DB_PREFIX . "cod_unit bu ON (pu.base_unit_id = bu.unit_id)
            LEFT JOIN " . DB_PREFIX . "cod_unit_description bud ON (bu.unit_id = bud.unit_id)
            WHERE p.product_id = '" . (int)$product_id . "'
            AND pd.language_id = '" . (int)$this->config->get('config_language_id') . "'
            AND bud.language_id = '" . (int)$this->config->get('config_language_id') . "'
        ");

        return $query->row;
    }

    /**
     * إضافة منتج جديد
     */
    public function addProduct($data) {
        // إنشاء كود المنتج التلقائي
        if (empty($data['sku'])) {
            $data['sku'] = $this->generateProductCode($data);
        }

        // إدراج المنتج الأساسي
        $this->db->query("
            INSERT INTO " . DB_PREFIX . "cod_product
            SET model = '" . $this->db->escape($data['model']) . "',
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
                image = '" . $this->db->escape($data['image']) . "',
                manufacturer_id = '" . (int)$data['manufacturer_id'] . "',
                shipping = '" . (int)$data['shipping'] . "',
                price = '" . (float)$data['price'] . "',
                points = '" . (int)$data['points'] . "',
                tax_class_id = '" . (int)$data['tax_class_id'] . "',
                date_available = '" . $this->db->escape($data['date_available']) . "',
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

        // إدراج وصف المنتج
        if (isset($data['product_description'])) {
            foreach ($data['product_description'] as $language_id => $value) {
                $this->db->query("
                    INSERT INTO " . DB_PREFIX . "product_description
                    SET product_id = '" . (int)$product_id . "',
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

        // إدراج معلومات المخزون
        $this->addProductInventory($product_id, $data);

        // إدراج معلومات التسعير
        $this->addProductPricing($product_id, $data);

        // إدراج معلومات الوحدات
        $this->addProductUnits($product_id, $data);

        // إدراج الفئات
        if (isset($data['product_category'])) {
            foreach ($data['product_category'] as $category_id) {
                $this->db->query("
                    INSERT INTO " . DB_PREFIX . "product_to_category
                    SET product_id = '" . (int)$product_id . "',
                        category_id = '" . (int)$category_id . "'
                ");
            }
        }

        // إنشاء باركود تلقائي إذا كان مطلوباً
        if (!empty($data['auto_generate_barcode'])) {
            $this->load->model('inventory/barcode_management');
            $this->model_inventory_barcode_management->generateProductBarcodes(
                $product_id,
                array('EAN13'),
                !empty($data['include_units']),
                !empty($data['include_options'])
            );
        }

        return $product_id;
    }

    /**
     * تحديث منتج
     */
    public function editProduct($product_id, $data) {
        // تحديث المنتج الأساسي
        $this->db->query("
            UPDATE " . DB_PREFIX . "cod_product
            SET model = '" . $this->db->escape($data['model']) . "',
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
                image = '" . $this->db->escape($data['image']) . "',
                manufacturer_id = '" . (int)$data['manufacturer_id'] . "',
                shipping = '" . (int)$data['shipping'] . "',
                price = '" . (float)$data['price'] . "',
                points = '" . (int)$data['points'] . "',
                tax_class_id = '" . (int)$data['tax_class_id'] . "',
                date_available = '" . $this->db->escape($data['date_available']) . "',
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

        // تحديث وصف المنتج
        $this->db->query("DELETE FROM " . DB_PREFIX . "product_description WHERE product_id = '" . (int)$product_id . "'");

        if (isset($data['product_description'])) {
            foreach ($data['product_description'] as $language_id => $value) {
                $this->db->query("
                    INSERT INTO " . DB_PREFIX . "product_description
                    SET product_id = '" . (int)$product_id . "',
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

        // تحديث معلومات المخزون
        $this->updateProductInventory($product_id, $data);

        // تحديث معلومات التسعير
        $this->updateProductPricing($product_id, $data);

        // تحديث معلومات الوحدات
        $this->updateProductUnits($product_id, $data);

        // تحديث الفئات
        $this->db->query("DELETE FROM " . DB_PREFIX . "product_to_category WHERE product_id = '" . (int)$product_id . "'");

        if (isset($data['product_category'])) {
            foreach ($data['product_category'] as $category_id) {
                $this->db->query("
                    INSERT INTO " . DB_PREFIX . "product_to_category
                    SET product_id = '" . (int)$product_id . "',
                        category_id = '" . (int)$category_id . "'
                ");
            }
        }
    }

    /**
     * حذف منتج
     */
    public function deleteProduct($product_id) {
        $this->db->query("DELETE FROM " . DB_PREFIX . "cod_product WHERE product_id = '" . (int)$product_id . "'");
        $this->db->query("DELETE FROM " . DB_PREFIX . "product_description WHERE product_id = '" . (int)$product_id . "'");
        $this->db->query("DELETE FROM " . DB_PREFIX . "cod_product_inventory WHERE product_id = '" . (int)$product_id . "'");
        $this->db->query("DELETE FROM " . DB_PREFIX . "cod_product_pricing WHERE product_id = '" . (int)$product_id . "'");
        $this->db->query("DELETE FROM " . DB_PREFIX . "cod_product_unit WHERE product_id = '" . (int)$product_id . "'");
        $this->db->query("DELETE FROM " . DB_PREFIX . "product_to_category WHERE product_id = '" . (int)$product_id . "'");
        $this->db->query("DELETE FROM " . DB_PREFIX . "cod_product_barcode WHERE product_id = '" . (int)$product_id . "'");
    }

    /**
     * إضافة معلومات المخزون
     */
    private function addProductInventory($product_id, $data) {
        $this->db->query("
            INSERT INTO " . DB_PREFIX . "cod_product_inventory
            SET product_id = '" . (int)$product_id . "',
                branch_id = '" . (int)($data['branch_id'] ?: 1) . "',
                available_quantity = '" . (float)($data['quantity'] ?: 0) . "',
                reserved_quantity = 0,
                on_order_quantity = 0,
                reorder_level = '" . (float)($data['minimum'] ?: 0) . "',
                max_stock_level = '" . (float)($data['max_stock_level'] ?: 1000) . "',
                avg_cost = '" . (float)($data['avg_cost'] ?: $data['price']) . "',
                last_cost = '" . (float)($data['last_cost'] ?: $data['price']) . "',
                standard_cost = '" . (float)($data['standard_cost'] ?: $data['price']) . "',
                date_added = NOW(),
                date_modified = NOW()
        ");
    }

    /**
     * تحديث معلومات المخزون
     */
    private function updateProductInventory($product_id, $data) {
        $this->db->query("
            UPDATE " . DB_PREFIX . "cod_product_inventory
            SET available_quantity = '" . (float)($data['quantity'] ?: 0) . "',
                reorder_level = '" . (float)($data['minimum'] ?: 0) . "',
                max_stock_level = '" . (float)($data['max_stock_level'] ?: 1000) . "',
                avg_cost = '" . (float)($data['avg_cost'] ?: $data['price']) . "',
                last_cost = '" . (float)($data['last_cost'] ?: $data['price']) . "',
                standard_cost = '" . (float)($data['standard_cost'] ?: $data['price']) . "',
                date_modified = NOW()
            WHERE product_id = '" . (int)$product_id . "'
        ");
    }

    /**
     * إضافة معلومات التسعير
     */
    private function addProductPricing($product_id, $data) {
        $this->db->query("
            INSERT INTO " . DB_PREFIX . "cod_product_pricing
            SET product_id = '" . (int)$product_id . "',
                basic_price = '" . (float)($data['price'] ?: 0) . "',
                offer_price = '" . (float)($data['offer_price'] ?: 0) . "',
                wholesale_price = '" . (float)($data['wholesale_price'] ?: 0) . "',
                semi_wholesale_price = '" . (float)($data['semi_wholesale_price'] ?: 0) . "',
                special_price = '" . (float)($data['special_price'] ?: 0) . "',
                pos_price = '" . (float)($data['pos_price'] ?: $data['price']) . "',
                online_price = '" . (float)($data['online_price'] ?: $data['price']) . "',
                cost_price = '" . (float)($data['cost_price'] ?: 0) . "',
                margin_percentage = '" . (float)($data['margin_percentage'] ?: 0) . "',
                markup_percentage = '" . (float)($data['markup_percentage'] ?: 0) . "',
                date_added = NOW(),
                date_modified = NOW()
        ");
    }

    /**
     * تحديث معلومات التسعير
     */
    private function updateProductPricing($product_id, $data) {
        $this->db->query("
            UPDATE " . DB_PREFIX . "cod_product_pricing
            SET basic_price = '" . (float)($data['price'] ?: 0) . "',
                offer_price = '" . (float)($data['offer_price'] ?: 0) . "',
                wholesale_price = '" . (float)($data['wholesale_price'] ?: 0) . "',
                semi_wholesale_price = '" . (float)($data['semi_wholesale_price'] ?: 0) . "',
                special_price = '" . (float)($data['special_price'] ?: 0) . "',
                pos_price = '" . (float)($data['pos_price'] ?: $data['price']) . "',
                online_price = '" . (float)($data['online_price'] ?: $data['price']) . "',
                cost_price = '" . (float)($data['cost_price'] ?: 0) . "',
                margin_percentage = '" . (float)($data['margin_percentage'] ?: 0) . "',
                markup_percentage = '" . (float)($data['markup_percentage'] ?: 0) . "',
                date_modified = NOW()
            WHERE product_id = '" . (int)$product_id . "'
        ");
    }

    /**
     * إضافة معلومات الوحدات
     */
    private function addProductUnits($product_id, $data) {
        if (!empty($data['base_unit_id'])) {
            $this->db->query("
                INSERT INTO " . DB_PREFIX . "cod_product_unit
                SET product_id = '" . (int)$product_id . "',
                    base_unit_id = '" . (int)$data['base_unit_id'] . "',
                    date_added = NOW(),
                    date_modified = NOW()
            ");
        }
    }

    /**
     * تحديث معلومات الوحدات
     */
    private function updateProductUnits($product_id, $data) {
        $this->db->query("DELETE FROM " . DB_PREFIX . "cod_product_unit WHERE product_id = '" . (int)$product_id . "'");

        if (!empty($data['base_unit_id'])) {
            $this->db->query("
                INSERT INTO " . DB_PREFIX . "cod_product_unit
                SET product_id = '" . (int)$product_id . "',
                    base_unit_id = '" . (int)$data['base_unit_id'] . "',
                    date_added = NOW(),
                    date_modified = NOW()
            ");
        }
    }

    /**
     * توليد كود المنتج التلقائي
     */
    public function generateProductCode($data) {
        // الحصول على البادئة من الإعدادات
        $prefix = $this->config->get('config_product_code_prefix') ?: 'PRD';

        // الحصول على آخر رقم
        $query = $this->db->query("
            SELECT MAX(CAST(SUBSTRING(sku, " . (strlen($prefix) + 1) . ") AS UNSIGNED)) as last_number
            FROM " . DB_PREFIX . "cod_product
            WHERE sku LIKE '" . $prefix . "%'
            AND sku REGEXP '^" . $prefix . "[0-9]+$'
        ");

        $last_number = $query->row['last_number'] ?: 0;
        $new_number = $last_number + 1;

        // تنسيق الرقم مع الأصفار
        $formatted_number = str_pad($new_number, 6, '0', STR_PAD_LEFT);

        return $prefix . $formatted_number;
    }

    /**
     * الحصول على إحصائيات المنتجات
     */
    public function getProductStatistics($data = array()) {
        $sql = "
            SELECT
                COUNT(*) as total_products,
                SUM(CASE WHEN p.status = 1 THEN 1 ELSE 0 END) as active_products,
                SUM(CASE WHEN p.status = 0 THEN 1 ELSE 0 END) as inactive_products,
                SUM(CASE WHEN pi.available_quantity <= 0 THEN 1 ELSE 0 END) as out_of_stock_products,
                SUM(CASE WHEN pi.available_quantity <= pi.reorder_level AND pi.available_quantity > 0 THEN 1 ELSE 0 END) as low_stock_products,
                SUM(CASE WHEN pi.available_quantity >= pi.max_stock_level THEN 1 ELSE 0 END) as overstock_products,
                SUM(pi.available_quantity * pi.avg_cost) as total_inventory_value,
                AVG(p.price) as avg_selling_price,
                AVG(pi.avg_cost) as avg_cost_price,
                SUM(pi.available_quantity) as total_quantity,
                COUNT(DISTINCT p.manufacturer_id) as total_manufacturers,
                COUNT(DISTINCT ptc.category_id) as total_categories
            FROM " . DB_PREFIX . "cod_product p
            LEFT JOIN " . DB_PREFIX . "cod_product_inventory pi ON (p.product_id = pi.product_id)
            LEFT JOIN " . DB_PREFIX . "product_to_category ptc ON (p.product_id = ptc.product_id)
            WHERE 1=1
        ";

        if (!empty($data['filter_date_from'])) {
            $sql .= " AND DATE(p.date_added) >= '" . $this->db->escape($data['filter_date_from']) . "'";
        }

        if (!empty($data['filter_date_to'])) {
            $sql .= " AND DATE(p.date_added) <= '" . $this->db->escape($data['filter_date_to']) . "'";
        }

        $query = $this->db->query($sql);

        return $query->row;
    }

    /**
     * الحصول على أفضل المنتجات مبيعاً
     */
    public function getTopSellingProducts($limit = 10, $days = 30) {
        $query = $this->db->query("
            SELECT
                p.product_id,
                pd.name,
                p.model,
                p.sku,
                p.price,
                pi.available_quantity,
                SUM(op.quantity) as total_sold,
                SUM(op.quantity * op.price) as total_revenue,
                AVG(op.price) as avg_selling_price
            FROM " . DB_PREFIX . "cod_product p
            LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id)
            LEFT JOIN " . DB_PREFIX . "cod_product_inventory pi ON (p.product_id = pi.product_id)
            LEFT JOIN " . DB_PREFIX . "order_product op ON (p.product_id = op.product_id)
            LEFT JOIN " . DB_PREFIX . "order o ON (op.order_id = o.order_id)
            WHERE pd.language_id = '" . (int)$this->config->get('config_language_id') . "'
            AND o.order_status_id > 0
            AND DATE(o.date_added) >= DATE_SUB(CURDATE(), INTERVAL " . (int)$days . " DAY)
            GROUP BY p.product_id
            ORDER BY total_sold DESC
            LIMIT " . (int)$limit
        );

        return $query->rows;
    }

    /**
     * الحصول على المنتجات منخفضة المخزون
     */
    public function getLowStockProducts($limit = 20) {
        $query = $this->db->query("
            SELECT
                p.product_id,
                pd.name,
                p.model,
                p.sku,
                pi.available_quantity,
                pi.reorder_level,
                pi.max_stock_level,
                (pi.reorder_level - pi.available_quantity) as shortage_quantity,
                (pi.available_quantity * pi.avg_cost) as current_value
            FROM " . DB_PREFIX . "cod_product p
            LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id)
            LEFT JOIN " . DB_PREFIX . "cod_product_inventory pi ON (p.product_id = pi.product_id)
            WHERE pd.language_id = '" . (int)$this->config->get('config_language_id') . "'
            AND p.status = 1
            AND pi.available_quantity <= pi.reorder_level
            AND pi.available_quantity >= 0
            ORDER BY (pi.available_quantity / NULLIF(pi.reorder_level, 0)) ASC
            LIMIT " . (int)$limit
        );

        return $query->rows;
    }

    /**
     * الحصول على المنتجات عالية المخزون
     */
    public function getOverstockProducts($limit = 20) {
        $query = $this->db->query("
            SELECT
                p.product_id,
                pd.name,
                p.model,
                p.sku,
                pi.available_quantity,
                pi.max_stock_level,
                (pi.available_quantity - pi.max_stock_level) as excess_quantity,
                (pi.available_quantity * pi.avg_cost) as current_value,
                ((pi.available_quantity - pi.max_stock_level) * pi.avg_cost) as excess_value
            FROM " . DB_PREFIX . "cod_product p
            LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id)
            LEFT JOIN " . DB_PREFIX . "cod_product_inventory pi ON (p.product_id = pi.product_id)
            WHERE pd.language_id = '" . (int)$this->config->get('config_language_id') . "'
            AND p.status = 1
            AND pi.available_quantity > pi.max_stock_level
            ORDER BY excess_quantity DESC
            LIMIT " . (int)$limit
        );

        return $query->rows;
    }

    /**
     * الحصول على المنتجات حسب الفئة
     */
    public function getProductsByCategory($category_id) {
        $query = $this->db->query("
            SELECT
                p.product_id,
                pd.name,
                p.model,
                p.sku,
                p.price,
                pi.available_quantity,
                p.status
            FROM " . DB_PREFIX . "cod_product p
            LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id)
            LEFT JOIN " . DB_PREFIX . "cod_product_inventory pi ON (p.product_id = pi.product_id)
            LEFT JOIN " . DB_PREFIX . "product_to_category ptc ON (p.product_id = ptc.product_id)
            WHERE pd.language_id = '" . (int)$this->config->get('config_language_id') . "'
            AND ptc.category_id = '" . (int)$category_id . "'
            ORDER BY pd.name ASC
        ");

        return $query->rows;
    }

    /**
     * الحصول على المنتجات حسب الشركة المصنعة
     */
    public function getProductsByManufacturer($manufacturer_id) {
        $query = $this->db->query("
            SELECT
                p.product_id,
                pd.name,
                p.model,
                p.sku,
                p.price,
                pi.available_quantity,
                p.status
            FROM " . DB_PREFIX . "cod_product p
            LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id)
            LEFT JOIN " . DB_PREFIX . "cod_product_inventory pi ON (p.product_id = pi.product_id)
            WHERE pd.language_id = '" . (int)$this->config->get('config_language_id') . "'
            AND p.manufacturer_id = '" . (int)$manufacturer_id . "'
            ORDER BY pd.name ASC
        ");

        return $query->rows;
    }

    /**
     * البحث في المنتجات
     */
    public function searchProducts($search, $limit = 20) {
        $query = $this->db->query("
            SELECT
                p.product_id,
                pd.name,
                p.model,
                p.sku,
                p.price,
                pi.available_quantity,
                p.status,
                MATCH(pd.name, pd.description, pd.tag) AGAINST('" . $this->db->escape($search) . "') as relevance
            FROM " . DB_PREFIX . "cod_product p
            LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id)
            LEFT JOIN " . DB_PREFIX . "cod_product_inventory pi ON (p.product_id = pi.product_id)
            WHERE pd.language_id = '" . (int)$this->config->get('config_language_id') . "'
            AND (
                pd.name LIKE '%" . $this->db->escape($search) . "%' OR
                pd.description LIKE '%" . $this->db->escape($search) . "%' OR
                pd.tag LIKE '%" . $this->db->escape($search) . "%' OR
                p.model LIKE '%" . $this->db->escape($search) . "%' OR
                p.sku LIKE '%" . $this->db->escape($search) . "%' OR
                MATCH(pd.name, pd.description, pd.tag) AGAINST('" . $this->db->escape($search) . "')
            )
            ORDER BY relevance DESC, pd.name ASC
            LIMIT " . (int)$limit
        );

        return $query->rows;
    }

    /**
     * تحديث كميات المخزون
     */
    public function updateStock($product_id, $quantity, $operation = 'set') {
        if ($operation == 'add') {
            $this->db->query("
                UPDATE " . DB_PREFIX . "cod_product_inventory
                SET available_quantity = available_quantity + '" . (float)$quantity . "',
                    date_modified = NOW()
                WHERE product_id = '" . (int)$product_id . "'
            ");
        } elseif ($operation == 'subtract') {
            $this->db->query("
                UPDATE " . DB_PREFIX . "cod_product_inventory
                SET available_quantity = GREATEST(0, available_quantity - '" . (float)$quantity . "'),
                    date_modified = NOW()
                WHERE product_id = '" . (int)$product_id . "'
            ");
        } else {
            $this->db->query("
                UPDATE " . DB_PREFIX . "cod_product_inventory
                SET available_quantity = '" . (float)$quantity . "',
                    date_modified = NOW()
                WHERE product_id = '" . (int)$product_id . "'
            ");
        }

        // تحديث الكمية في الجدول الأساسي أيضاً للتوافق
        $this->db->query("
            UPDATE " . DB_PREFIX . "cod_product
            SET quantity = (SELECT available_quantity FROM " . DB_PREFIX . "cod_product_inventory WHERE product_id = '" . (int)$product_id . "'),
                date_modified = NOW()
            WHERE product_id = '" . (int)$product_id . "'
        ");
    }

    /**
     * تصدير البيانات للإكسل
     */
    public function exportToExcel($data = array()) {
        // إزالة حدود الصفحات للتصدير
        unset($data['start']);
        unset($data['limit']);

        return $this->getProducts($data);
    }
}
