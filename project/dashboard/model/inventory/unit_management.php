<?php
/**
 * نموذج إدارة الوحدات والتحويلات المتطورة (Advanced Unit Management Model)
 *
 * الهدف: توفير نظام شامل لإدارة وحدات القياس مع تحويلات تلقائية
 * الميزات: تحويلات متعددة المستويات، تسعير مختلف للوحدات، ربط بالمنتجات والباركود
 * التكامل: مع المنتجات والباركود والمخزون والمبيعات والمشتريات
 *
 * @author ERP Team
 * @version 1.0
 * @since 2024
 */

class ModelInventoryUnitManagement extends Model {

    /**
     * الحصول على الوحدات مع فلاتر متقدمة
     */
    public function getUnits($data = array()) {
        $sql = "
            SELECT
                u.unit_id,
                ud.name,
                ud.symbol,
                ud.description,
                u.unit_type,
                u.base_unit_id,
                bu.name as base_unit_name,
                bud.symbol as base_unit_symbol,
                u.conversion_factor,
                u.is_base_unit,
                u.is_active,
                u.sort_order,
                u.date_added,
                u.date_modified,

                -- إحصائيات الاستخدام
                (SELECT COUNT(DISTINCT p.product_id) FROM " . DB_PREFIX . "cod_product_unit pu
                 LEFT JOIN " . DB_PREFIX . "cod_product p ON (pu.product_id = p.product_id)
                 WHERE pu.unit_id = u.unit_id) as products_count,

                (SELECT COUNT(*) FROM " . DB_PREFIX . "cod_product_barcode pb
                 WHERE pb.unit_id = u.unit_id) as barcodes_count,

                (SELECT COUNT(*) FROM " . DB_PREFIX . "cod_inventory_movement im
                 WHERE im.unit_id = u.unit_id
                 AND DATE(im.date_added) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)) as movements_30_days,

                -- معلومات التسعير
                (SELECT COUNT(*) FROM " . DB_PREFIX . "cod_unit_pricing up
                 WHERE up.unit_id = u.unit_id) as pricing_levels,

                -- الوحدات الفرعية
                (SELECT COUNT(*) FROM " . DB_PREFIX . "cod_unit sub
                 WHERE sub.base_unit_id = u.unit_id) as sub_units_count,

                -- حساب معامل التحويل الإجمالي للوحدة الأساسية
                CASE
                    WHEN u.is_base_unit = 1 THEN 1
                    WHEN u.base_unit_id IS NOT NULL THEN
                        u.conversion_factor * COALESCE((
                            SELECT conversion_factor FROM " . DB_PREFIX . "cod_unit parent
                            WHERE parent.unit_id = u.base_unit_id
                        ), 1)
                    ELSE u.conversion_factor
                END as total_conversion_factor

            FROM " . DB_PREFIX . "cod_unit u
            LEFT JOIN " . DB_PREFIX . "cod_unit_description ud ON (u.unit_id = ud.unit_id)
            LEFT JOIN " . DB_PREFIX . "cod_unit bu ON (u.base_unit_id = bu.unit_id)
            LEFT JOIN " . DB_PREFIX . "cod_unit_description bud ON (bu.unit_id = bud.unit_id)
            WHERE ud.language_id = '" . (int)$this->config->get('config_language_id') . "'
            AND (bud.language_id = '" . (int)$this->config->get('config_language_id') . "' OR bud.language_id IS NULL)
        ";

        // تطبيق الفلاتر
        if (!empty($data['filter_name'])) {
            $sql .= " AND ud.name LIKE '%" . $this->db->escape($data['filter_name']) . "%'";
        }

        if (!empty($data['filter_symbol'])) {
            $sql .= " AND ud.symbol LIKE '%" . $this->db->escape($data['filter_symbol']) . "%'";
        }

        if (!empty($data['filter_unit_type'])) {
            $sql .= " AND u.unit_type = '" . $this->db->escape($data['filter_unit_type']) . "'";
        }

        if (!empty($data['filter_base_unit_id'])) {
            $sql .= " AND u.base_unit_id = '" . (int)$data['filter_base_unit_id'] . "'";
        }

        if (isset($data['filter_is_base_unit']) && $data['filter_is_base_unit'] !== '') {
            $sql .= " AND u.is_base_unit = '" . (int)$data['filter_is_base_unit'] . "'";
        }

        if (isset($data['filter_is_active']) && $data['filter_is_active'] !== '') {
            $sql .= " AND u.is_active = '" . (int)$data['filter_is_active'] . "'";
        }

        // ترتيب النتائج
        $sort_data = array(
            'ud.name',
            'ud.symbol',
            'u.unit_type',
            'u.conversion_factor',
            'u.sort_order',
            'u.date_added',
            'products_count',
            'movements_30_days'
        );

        if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
            $sql .= " ORDER BY " . $data['sort'];
        } else {
            $sql .= " ORDER BY u.unit_type ASC, u.sort_order ASC, ud.name ASC";
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
     * الحصول على إجمالي عدد الوحدات
     */
    public function getTotalUnits($data = array()) {
        $sql = "
            SELECT COUNT(DISTINCT u.unit_id) AS total
            FROM " . DB_PREFIX . "cod_unit u
            LEFT JOIN " . DB_PREFIX . "cod_unit_description ud ON (u.unit_id = ud.unit_id)
            WHERE ud.language_id = '" . (int)$this->config->get('config_language_id') . "'
        ";

        // تطبيق نفس الفلاتر
        if (!empty($data['filter_name'])) {
            $sql .= " AND ud.name LIKE '%" . $this->db->escape($data['filter_name']) . "%'";
        }

        if (!empty($data['filter_symbol'])) {
            $sql .= " AND ud.symbol LIKE '%" . $this->db->escape($data['filter_symbol']) . "%'";
        }

        if (!empty($data['filter_unit_type'])) {
            $sql .= " AND u.unit_type = '" . $this->db->escape($data['filter_unit_type']) . "'";
        }

        $query = $this->db->query($sql);

        return $query->row['total'];
    }

    /**
     * الحصول على وحدة محددة
     */
    public function getUnit($unit_id) {
        $query = $this->db->query("
            SELECT
                u.*,
                ud.name,
                ud.symbol,
                ud.description,
                bu.name as base_unit_name,
                bud.symbol as base_unit_symbol
            FROM " . DB_PREFIX . "cod_unit u
            LEFT JOIN " . DB_PREFIX . "cod_unit_description ud ON (u.unit_id = ud.unit_id)
            LEFT JOIN " . DB_PREFIX . "cod_unit bu ON (u.base_unit_id = bu.unit_id)
            LEFT JOIN " . DB_PREFIX . "cod_unit_description bud ON (bu.unit_id = bud.unit_id)
            WHERE u.unit_id = '" . (int)$unit_id . "'
            AND ud.language_id = '" . (int)$this->config->get('config_language_id') . "'
            AND (bud.language_id = '" . (int)$this->config->get('config_language_id') . "' OR bud.language_id IS NULL)
        ");

        return $query->row;
    }

    /**
     * إضافة وحدة جديدة
     */
    public function addUnit($data) {
        // التحقق من عدم تكرار الرمز
        if ($this->checkSymbolExists($data['unit_description'][1]['symbol'])) {
            return false;
        }

        // إدراج الوحدة الأساسية
        $this->db->query("
            INSERT INTO " . DB_PREFIX . "cod_unit
            SET unit_type = '" . $this->db->escape($data['unit_type']) . "',
                base_unit_id = " . (isset($data['base_unit_id']) && $data['base_unit_id'] ? "'" . (int)$data['base_unit_id'] . "'" : "NULL") . ",
                conversion_factor = '" . (float)($data['conversion_factor'] ?: 1) . "',
                is_base_unit = '" . (int)($data['is_base_unit'] ?: 0) . "',
                is_active = '" . (int)($data['is_active'] ?: 1) . "',
                sort_order = '" . (int)($data['sort_order'] ?: 0) . "',
                date_added = NOW(),
                date_modified = NOW()
        ");

        $unit_id = $this->db->getLastId();

        // إدراج وصف الوحدة
        if (isset($data['unit_description'])) {
            foreach ($data['unit_description'] as $language_id => $value) {
                $this->db->query("
                    INSERT INTO " . DB_PREFIX . "cod_unit_description
                    SET unit_id = '" . (int)$unit_id . "',
                        language_id = '" . (int)$language_id . "',
                        name = '" . $this->db->escape($value['name']) . "',
                        symbol = '" . $this->db->escape($value['symbol']) . "',
                        description = '" . $this->db->escape($value['description']) . "'
                ");
            }
        }

        // إدراج مستويات التسعير إذا كانت موجودة
        if (isset($data['unit_pricing'])) {
            $this->addUnitPricing($unit_id, $data['unit_pricing']);
        }

        // إنشاء تحويلات تلقائية للوحدات ذات الصلة
        $this->updateConversions($unit_id);

        return $unit_id;
    }

    /**
     * تحديث وحدة
     */
    public function editUnit($unit_id, $data) {
        // التحقق من عدم تكرار الرمز (باستثناء الوحدة الحالية)
        if ($this->checkSymbolExists($data['unit_description'][1]['symbol'], $unit_id)) {
            return false;
        }

        // تحديث الوحدة الأساسية
        $this->db->query("
            UPDATE " . DB_PREFIX . "cod_unit
            SET unit_type = '" . $this->db->escape($data['unit_type']) . "',
                base_unit_id = " . (isset($data['base_unit_id']) && $data['base_unit_id'] ? "'" . (int)$data['base_unit_id'] . "'" : "NULL") . ",
                conversion_factor = '" . (float)($data['conversion_factor'] ?: 1) . "',
                is_base_unit = '" . (int)($data['is_base_unit'] ?: 0) . "',
                is_active = '" . (int)($data['is_active'] ?: 1) . "',
                sort_order = '" . (int)($data['sort_order'] ?: 0) . "',
                date_modified = NOW()
            WHERE unit_id = '" . (int)$unit_id . "'
        ");

        // تحديث وصف الوحدة
        $this->db->query("DELETE FROM " . DB_PREFIX . "cod_unit_description WHERE unit_id = '" . (int)$unit_id . "'");

        if (isset($data['unit_description'])) {
            foreach ($data['unit_description'] as $language_id => $value) {
                $this->db->query("
                    INSERT INTO " . DB_PREFIX . "cod_unit_description
                    SET unit_id = '" . (int)$unit_id . "',
                        language_id = '" . (int)$language_id . "',
                        name = '" . $this->db->escape($value['name']) . "',
                        symbol = '" . $this->db->escape($value['symbol']) . "',
                        description = '" . $this->db->escape($value['description']) . "'
                ");
            }
        }

        // تحديث مستويات التسعير
        $this->db->query("DELETE FROM " . DB_PREFIX . "cod_unit_pricing WHERE unit_id = '" . (int)$unit_id . "'");

        if (isset($data['unit_pricing'])) {
            $this->addUnitPricing($unit_id, $data['unit_pricing']);
        }

        // تحديث التحويلات
        $this->updateConversions($unit_id);

        return true;
    }

    /**
     * حذف وحدة
     */
    public function deleteUnit($unit_id) {
        // التحقق من عدم استخدام الوحدة
        $check_usage = $this->checkUnitUsage($unit_id);
        if ($check_usage['in_use']) {
            return array('error' => 'لا يمكن حذف الوحدة لأنها مستخدمة في: ' . implode(', ', $check_usage['used_in']));
        }

        $this->db->query("DELETE FROM " . DB_PREFIX . "cod_unit WHERE unit_id = '" . (int)$unit_id . "'");
        $this->db->query("DELETE FROM " . DB_PREFIX . "cod_unit_description WHERE unit_id = '" . (int)$unit_id . "'");
        $this->db->query("DELETE FROM " . DB_PREFIX . "cod_unit_pricing WHERE unit_id = '" . (int)$unit_id . "'");
        $this->db->query("DELETE FROM " . DB_PREFIX . "cod_unit_conversion WHERE from_unit_id = '" . (int)$unit_id . "' OR to_unit_id = '" . (int)$unit_id . "'");

        return array('success' => true);
    }

    /**
     * التحقق من تكرار الرمز
     */
    private function checkSymbolExists($symbol, $exclude_unit_id = null) {
        $sql = "
            SELECT COUNT(*) as count
            FROM " . DB_PREFIX . "cod_unit_description
            WHERE symbol = '" . $this->db->escape($symbol) . "'
            AND language_id = '" . (int)$this->config->get('config_language_id') . "'
        ";

        if ($exclude_unit_id) {
            $sql .= " AND unit_id != '" . (int)$exclude_unit_id . "'";
        }

        $query = $this->db->query($sql);

        return $query->row['count'] > 0;
    }

    /**
     * التحقق من استخدام الوحدة
     */
    private function checkUnitUsage($unit_id) {
        $used_in = array();

        // التحقق من المنتجات
        $products_query = $this->db->query("
            SELECT COUNT(*) as count
            FROM " . DB_PREFIX . "cod_product_unit
            WHERE unit_id = '" . (int)$unit_id . "'
        ");

        if ($products_query->row['count'] > 0) {
            $used_in[] = $products_query->row['count'] . ' منتج';
        }

        // التحقق من الباركود
        $barcodes_query = $this->db->query("
            SELECT COUNT(*) as count
            FROM " . DB_PREFIX . "cod_product_barcode
            WHERE unit_id = '" . (int)$unit_id . "'
        ");

        if ($barcodes_query->row['count'] > 0) {
            $used_in[] = $barcodes_query->row['count'] . ' باركود';
        }

        // التحقق من حركات المخزون
        $movements_query = $this->db->query("
            SELECT COUNT(*) as count
            FROM " . DB_PREFIX . "cod_inventory_movement
            WHERE unit_id = '" . (int)$unit_id . "'
        ");

        if ($movements_query->row['count'] > 0) {
            $used_in[] = $movements_query->row['count'] . ' حركة مخزون';
        }

        // التحقق من الوحدات الفرعية
        $sub_units_query = $this->db->query("
            SELECT COUNT(*) as count
            FROM " . DB_PREFIX . "cod_unit
            WHERE base_unit_id = '" . (int)$unit_id . "'
        ");

        if ($sub_units_query->row['count'] > 0) {
            $used_in[] = $sub_units_query->row['count'] . ' وحدة فرعية';
        }

        return array(
            'in_use' => !empty($used_in),
            'used_in' => $used_in
        );
    }

    /**
     * إضافة مستويات التسعير للوحدة
     */
    private function addUnitPricing($unit_id, $pricing_data) {
        foreach ($pricing_data as $pricing) {
            if (!empty($pricing['price_level']) && !empty($pricing['price_factor'])) {
                $this->db->query("
                    INSERT INTO " . DB_PREFIX . "cod_unit_pricing
                    SET unit_id = '" . (int)$unit_id . "',
                        price_level = '" . $this->db->escape($pricing['price_level']) . "',
                        price_factor = '" . (float)$pricing['price_factor'] . "',
                        is_active = '" . (int)($pricing['is_active'] ?: 1) . "',
                        date_added = NOW()
                ");
            }
        }
    }

    /**
     * تحديث جدول التحويلات
     */
    private function updateConversions($unit_id) {
        // حذف التحويلات القديمة للوحدة
        $this->db->query("DELETE FROM " . DB_PREFIX . "cod_unit_conversion WHERE from_unit_id = '" . (int)$unit_id . "' OR to_unit_id = '" . (int)$unit_id . "'");

        // الحصول على معلومات الوحدة
        $unit_info = $this->getUnit($unit_id);

        if ($unit_info) {
            // إنشاء تحويلات مع الوحدات من نفس النوع
            $related_units = $this->getUnitsByType($unit_info['unit_type']);

            foreach ($related_units as $related_unit) {
                if ($related_unit['unit_id'] != $unit_id) {
                    $conversion_factor = $this->calculateConversionFactor($unit_info, $related_unit);

                    if ($conversion_factor > 0) {
                        $this->db->query("
                            INSERT INTO " . DB_PREFIX . "cod_unit_conversion
                            SET from_unit_id = '" . (int)$unit_id . "',
                                to_unit_id = '" . (int)$related_unit['unit_id'] . "',
                                conversion_factor = '" . (float)$conversion_factor . "',
                                date_added = NOW()
                        ");

                        // التحويل العكسي
                        $this->db->query("
                            INSERT INTO " . DB_PREFIX . "cod_unit_conversion
                            SET from_unit_id = '" . (int)$related_unit['unit_id'] . "',
                                to_unit_id = '" . (int)$unit_id . "',
                                conversion_factor = '" . (float)(1 / $conversion_factor) . "',
                                date_added = NOW()
                        ");
                    }
                }
            }
        }
    }

    /**
     * الحصول على الوحدات حسب النوع
     */
    public function getUnitsByType($unit_type) {
        $query = $this->db->query("
            SELECT
                u.*,
                ud.name,
                ud.symbol
            FROM " . DB_PREFIX . "cod_unit u
            LEFT JOIN " . DB_PREFIX . "cod_unit_description ud ON (u.unit_id = ud.unit_id)
            WHERE u.unit_type = '" . $this->db->escape($unit_type) . "'
            AND ud.language_id = '" . (int)$this->config->get('config_language_id') . "'
            AND u.is_active = 1
            ORDER BY u.sort_order ASC, ud.name ASC
        ");

        return $query->rows;
    }

    /**
     * حساب معامل التحويل بين وحدتين
     */
    public function calculateConversionFactor($from_unit, $to_unit) {
        // إذا كانت نفس الوحدة
        if ($from_unit['unit_id'] == $to_unit['unit_id']) {
            return 1;
        }

        // إذا كانت من نوع مختلف
        if ($from_unit['unit_type'] != $to_unit['unit_type']) {
            return 0;
        }

        // حساب معامل التحويل للوحدة الأساسية
        $from_base_factor = $this->getBaseConversionFactor($from_unit);
        $to_base_factor = $this->getBaseConversionFactor($to_unit);

        if ($to_base_factor == 0) {
            return 0;
        }

        return $from_base_factor / $to_base_factor;
    }

    /**
     * الحصول على معامل التحويل للوحدة الأساسية
     */
    private function getBaseConversionFactor($unit) {
        if ($unit['is_base_unit'] == 1) {
            return 1;
        }

        $factor = $unit['conversion_factor'];

        // إذا كانت الوحدة لها وحدة أساسية
        if ($unit['base_unit_id']) {
            $base_unit = $this->getUnit($unit['base_unit_id']);
            if ($base_unit) {
                $factor *= $this->getBaseConversionFactor($base_unit);
            }
        }

        return $factor;
    }

    /**
     * تحويل كمية من وحدة إلى أخرى
     */
    public function convertQuantity($quantity, $from_unit_id, $to_unit_id) {
        if ($from_unit_id == $to_unit_id) {
            return $quantity;
        }

        // البحث عن معامل التحويل المحفوظ
        $query = $this->db->query("
            SELECT conversion_factor
            FROM " . DB_PREFIX . "cod_unit_conversion
            WHERE from_unit_id = '" . (int)$from_unit_id . "'
            AND to_unit_id = '" . (int)$to_unit_id . "'
        ");

        if ($query->num_rows) {
            return $quantity * $query->row['conversion_factor'];
        }

        // حساب التحويل ديناميكياً
        $from_unit = $this->getUnit($from_unit_id);
        $to_unit = $this->getUnit($to_unit_id);

        if ($from_unit && $to_unit) {
            $conversion_factor = $this->calculateConversionFactor($from_unit, $to_unit);
            return $quantity * $conversion_factor;
        }

        return 0;
    }

    /**
     * الحصول على أنواع الوحدات
     */
    public function getUnitTypes() {
        return array(
            'weight' => 'الوزن',
            'length' => 'الطول',
            'area' => 'المساحة',
            'volume' => 'الحجم',
            'quantity' => 'الكمية',
            'time' => 'الوقت',
            'temperature' => 'درجة الحرارة',
            'pressure' => 'الضغط',
            'energy' => 'الطاقة',
            'power' => 'القدرة',
            'speed' => 'السرعة',
            'currency' => 'العملة'
        );
    }

    /**
     * الحصول على الوحدات الأساسية
     */
    public function getBaseUnits() {
        $query = $this->db->query("
            SELECT
                u.unit_id,
                ud.name,
                ud.symbol,
                u.unit_type
            FROM " . DB_PREFIX . "cod_unit u
            LEFT JOIN " . DB_PREFIX . "cod_unit_description ud ON (u.unit_id = ud.unit_id)
            WHERE u.is_base_unit = 1
            AND u.is_active = 1
            AND ud.language_id = '" . (int)$this->config->get('config_language_id') . "'
            ORDER BY u.unit_type ASC, ud.name ASC
        ");

        return $query->rows;
    }

    /**
     * الحصول على الوحدات الفرعية لوحدة أساسية
     */
    public function getSubUnits($base_unit_id) {
        $query = $this->db->query("
            SELECT
                u.unit_id,
                ud.name,
                ud.symbol,
                u.conversion_factor,
                u.sort_order
            FROM " . DB_PREFIX . "cod_unit u
            LEFT JOIN " . DB_PREFIX . "cod_unit_description ud ON (u.unit_id = ud.unit_id)
            WHERE u.base_unit_id = '" . (int)$base_unit_id . "'
            AND u.is_active = 1
            AND ud.language_id = '" . (int)$this->config->get('config_language_id') . "'
            ORDER BY u.sort_order ASC, ud.name ASC
        ");

        return $query->rows;
    }

    /**
     * الحصول على إحصائيات الوحدات
     */
    public function getUnitStatistics() {
        $sql = "
            SELECT
                COUNT(*) as total_units,
                COUNT(CASE WHEN u.is_active = 1 THEN 1 END) as active_units,
                COUNT(CASE WHEN u.is_base_unit = 1 THEN 1 END) as base_units,
                COUNT(DISTINCT u.unit_type) as unit_types,

                -- إحصائيات الاستخدام
                (SELECT COUNT(DISTINCT pu.product_id) FROM " . DB_PREFIX . "cod_product_unit pu) as products_with_units,
                (SELECT COUNT(*) FROM " . DB_PREFIX . "cod_product_barcode pb WHERE pb.unit_id IS NOT NULL) as barcodes_with_units,
                (SELECT COUNT(*) FROM " . DB_PREFIX . "cod_inventory_movement im WHERE im.unit_id IS NOT NULL) as movements_with_units,

                -- أكثر الوحدات استخداماً
                (SELECT ud.name FROM " . DB_PREFIX . "cod_unit u2
                 LEFT JOIN " . DB_PREFIX . "cod_unit_description ud ON (u2.unit_id = ud.unit_id)
                 LEFT JOIN " . DB_PREFIX . "cod_product_unit pu ON (u2.unit_id = pu.unit_id)
                 WHERE ud.language_id = '" . (int)$this->config->get('config_language_id') . "'
                 GROUP BY u2.unit_id
                 ORDER BY COUNT(pu.product_id) DESC
                 LIMIT 1) as most_used_unit

            FROM " . DB_PREFIX . "cod_unit u
        ";

        $query = $this->db->query($sql);

        return $query->row;
    }

    /**
     * الحصول على تقرير استخدام الوحدات
     */
    public function getUnitUsageReport($limit = 20) {
        $query = $this->db->query("
            SELECT
                u.unit_id,
                ud.name,
                ud.symbol,
                u.unit_type,
                COUNT(DISTINCT pu.product_id) as products_count,
                COUNT(DISTINCT pb.barcode_id) as barcodes_count,
                COUNT(DISTINCT im.movement_id) as movements_count,

                -- آخر استخدام
                (SELECT MAX(date_added) FROM " . DB_PREFIX . "cod_inventory_movement im2
                 WHERE im2.unit_id = u.unit_id) as last_used_date,

                -- إجمالي الكميات المتحركة
                (SELECT SUM(quantity) FROM " . DB_PREFIX . "cod_inventory_movement im3
                 WHERE im3.unit_id = u.unit_id
                 AND DATE(im3.date_added) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)) as total_quantity_30_days

            FROM " . DB_PREFIX . "cod_unit u
            LEFT JOIN " . DB_PREFIX . "cod_unit_description ud ON (u.unit_id = ud.unit_id)
            LEFT JOIN " . DB_PREFIX . "cod_product_unit pu ON (u.unit_id = pu.unit_id)
            LEFT JOIN " . DB_PREFIX . "cod_product_barcode pb ON (u.unit_id = pb.unit_id)
            LEFT JOIN " . DB_PREFIX . "cod_inventory_movement im ON (u.unit_id = im.unit_id)
            WHERE ud.language_id = '" . (int)$this->config->get('config_language_id') . "'
            AND u.is_active = 1
            GROUP BY u.unit_id
            ORDER BY products_count DESC, movements_count DESC
            LIMIT " . (int)$limit
        );

        return $query->rows;
    }

    /**
     * الحصول على جدول التحويلات لوحدة معينة
     */
    public function getConversionTable($unit_id) {
        $query = $this->db->query("
            SELECT
                uc.to_unit_id,
                ud.name as to_unit_name,
                ud.symbol as to_unit_symbol,
                uc.conversion_factor
            FROM " . DB_PREFIX . "cod_unit_conversion uc
            LEFT JOIN " . DB_PREFIX . "cod_unit_description ud ON (uc.to_unit_id = ud.unit_id)
            WHERE uc.from_unit_id = '" . (int)$unit_id . "'
            AND ud.language_id = '" . (int)$this->config->get('config_language_id') . "'
            ORDER BY ud.name ASC
        ");

        return $query->rows;
    }

    /**
     * إنشاء وحدات افتراضية للنظام
     */
    public function createDefaultUnits() {
        $default_units = array(
            // وحدات الوزن
            array(
                'unit_type' => 'weight',
                'is_base_unit' => 1,
                'conversion_factor' => 1,
                'sort_order' => 1,
                'description' => array(
                    1 => array('name' => 'كيلوجرام', 'symbol' => 'كجم', 'description' => 'الوحدة الأساسية للوزن')
                )
            ),
            array(
                'unit_type' => 'weight',
                'base_unit_id' => 1, // كيلوجرام
                'conversion_factor' => 0.001,
                'sort_order' => 2,
                'description' => array(
                    1 => array('name' => 'جرام', 'symbol' => 'جم', 'description' => 'وحدة فرعية للوزن')
                )
            ),
            array(
                'unit_type' => 'weight',
                'base_unit_id' => 1, // كيلوجرام
                'conversion_factor' => 1000,
                'sort_order' => 3,
                'description' => array(
                    1 => array('name' => 'طن', 'symbol' => 'طن', 'description' => 'وحدة كبيرة للوزن')
                )
            ),

            // وحدات الكمية
            array(
                'unit_type' => 'quantity',
                'is_base_unit' => 1,
                'conversion_factor' => 1,
                'sort_order' => 1,
                'description' => array(
                    1 => array('name' => 'قطعة', 'symbol' => 'قطعة', 'description' => 'الوحدة الأساسية للعد')
                )
            ),
            array(
                'unit_type' => 'quantity',
                'base_unit_id' => 4, // قطعة
                'conversion_factor' => 12,
                'sort_order' => 2,
                'description' => array(
                    1 => array('name' => 'دستة', 'symbol' => 'دستة', 'description' => '12 قطعة')
                )
            ),
            array(
                'unit_type' => 'quantity',
                'base_unit_id' => 4, // قطعة
                'conversion_factor' => 24,
                'sort_order' => 3,
                'description' => array(
                    1 => array('name' => 'كرتونة', 'symbol' => 'كرتونة', 'description' => '24 قطعة')
                )
            ),

            // وحدات الحجم
            array(
                'unit_type' => 'volume',
                'is_base_unit' => 1,
                'conversion_factor' => 1,
                'sort_order' => 1,
                'description' => array(
                    1 => array('name' => 'لتر', 'symbol' => 'لتر', 'description' => 'الوحدة الأساسية للحجم')
                )
            ),
            array(
                'unit_type' => 'volume',
                'base_unit_id' => 7, // لتر
                'conversion_factor' => 0.001,
                'sort_order' => 2,
                'description' => array(
                    1 => array('name' => 'مليلتر', 'symbol' => 'مل', 'description' => 'وحدة فرعية للحجم')
                )
            )
        );

        foreach ($default_units as $unit_data) {
            // التحقق من عدم وجود الوحدة مسبقاً
            $existing = $this->db->query("
                SELECT COUNT(*) as count
                FROM " . DB_PREFIX . "cod_unit_description
                WHERE symbol = '" . $this->db->escape($unit_data['description'][1]['symbol']) . "'
                AND language_id = 1
            ");

            if ($existing->row['count'] == 0) {
                $this->addUnit(array(
                    'unit_type' => $unit_data['unit_type'],
                    'base_unit_id' => isset($unit_data['base_unit_id']) ? $unit_data['base_unit_id'] : null,
                    'conversion_factor' => $unit_data['conversion_factor'],
                    'is_base_unit' => isset($unit_data['is_base_unit']) ? $unit_data['is_base_unit'] : 0,
                    'is_active' => 1,
                    'sort_order' => $unit_data['sort_order'],
                    'unit_description' => $unit_data['description']
                ));
            }
        }
    }
}
