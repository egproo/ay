<?php
/**
 * نموذج إدارة المواقع والمناطق المتطورة (Advanced Location Management Model)
 *
 * الهدف: توفير نظام شامل لإدارة مواقع التخزين مع تنظيم هرمي
 * الميزات: خرائط تفاعلية، تتبع GPS، مناطق متعددة المستويات، تكامل مع المخزون
 * التكامل: مع المنتجات والمخزون والحركات والفروع والمستودعات
 *
 * @author ERP Team
 * @version 1.0
 * @since 2024
 */

class ModelInventoryLocationManagement extends Model {

    /**
     * الحصول على المواقع مع فلاتر متقدمة
     */
    public function getLocations($data = array()) {
        $sql = "
            SELECT
                l.location_id,
                ld.name,
                ld.description,
                l.location_code,
                l.location_type,
                l.parent_location_id,
                pl.name as parent_location_name,
                pld.name as parent_location_display_name,
                l.branch_id,
                b.name as branch_name,
                l.warehouse_id,
                w.name as warehouse_name,
                l.zone_id,
                z.name as zone_name,
                l.aisle,
                l.rack,
                l.shelf,
                l.bin,
                l.barcode,
                l.qr_code,
                l.capacity_weight,
                l.capacity_volume,
                l.capacity_units,
                l.current_weight,
                l.current_volume,
                l.current_units,
                l.temperature_min,
                l.temperature_max,
                l.humidity_min,
                l.humidity_max,
                l.is_active,
                l.is_pickable,
                l.is_receivable,
                l.is_countable,
                l.priority_level,
                l.gps_latitude,
                l.gps_longitude,
                l.sort_order,
                l.date_added,
                l.date_modified,

                -- إحصائيات الاستخدام
                (SELECT COUNT(DISTINCT p.product_id) FROM " . DB_PREFIX . "cod_product_location pl2
                 LEFT JOIN " . DB_PREFIX . "cod_product p ON (pl2.product_id = p.product_id)
                 WHERE pl2.location_id = l.location_id) as products_count,

                (SELECT COUNT(*) FROM " . DB_PREFIX . "cod_inventory_movement im
                 WHERE im.location_id = l.location_id
                 AND DATE(im.date_added) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)) as movements_30_days,

                (SELECT SUM(quantity) FROM " . DB_PREFIX . "cod_product_location pl3
                 WHERE pl3.location_id = l.location_id) as total_quantity,

                (SELECT SUM(quantity * cost_price) FROM " . DB_PREFIX . "cod_product_location pl4
                 LEFT JOIN " . DB_PREFIX . "cod_product p2 ON (pl4.product_id = p2.product_id)
                 WHERE pl4.location_id = l.location_id) as total_value,

                -- المواقع الفرعية
                (SELECT COUNT(*) FROM " . DB_PREFIX . "cod_location sub
                 WHERE sub.parent_location_id = l.location_id) as sub_locations_count,

                -- حساب نسبة الاستخدام
                CASE
                    WHEN l.capacity_units > 0 THEN
                        ROUND((l.current_units / l.capacity_units) * 100, 2)
                    ELSE 0
                END as usage_percentage,

                -- حالة الموقع
                CASE
                    WHEN l.current_units >= l.capacity_units THEN 'full'
                    WHEN l.current_units >= (l.capacity_units * 0.8) THEN 'high'
                    WHEN l.current_units >= (l.capacity_units * 0.5) THEN 'medium'
                    WHEN l.current_units > 0 THEN 'low'
                    ELSE 'empty'
                END as occupancy_status

            FROM " . DB_PREFIX . "cod_location l
            LEFT JOIN " . DB_PREFIX . "cod_location_description ld ON (l.location_id = ld.location_id)
            LEFT JOIN " . DB_PREFIX . "cod_location pl ON (l.parent_location_id = pl.location_id)
            LEFT JOIN " . DB_PREFIX . "cod_location_description pld ON (pl.location_id = pld.location_id)
            LEFT JOIN " . DB_PREFIX . "cod_branch b ON (l.branch_id = b.branch_id)
            LEFT JOIN " . DB_PREFIX . "cod_warehouse w ON (l.warehouse_id = w.warehouse_id)
            LEFT JOIN " . DB_PREFIX . "cod_zone z ON (l.zone_id = z.zone_id)
            WHERE ld.language_id = '" . (int)$this->config->get('config_language_id') . "'
            AND (pld.language_id = '" . (int)$this->config->get('config_language_id') . "' OR pld.language_id IS NULL)
        ";

        // تطبيق الفلاتر
        if (!empty($data['filter_name'])) {
            $sql .= " AND ld.name LIKE '%" . $this->db->escape($data['filter_name']) . "%'";
        }

        if (!empty($data['filter_location_code'])) {
            $sql .= " AND l.location_code LIKE '%" . $this->db->escape($data['filter_location_code']) . "%'";
        }

        if (!empty($data['filter_location_type'])) {
            $sql .= " AND l.location_type = '" . $this->db->escape($data['filter_location_type']) . "'";
        }

        if (!empty($data['filter_branch_id'])) {
            $sql .= " AND l.branch_id = '" . (int)$data['filter_branch_id'] . "'";
        }

        if (!empty($data['filter_warehouse_id'])) {
            $sql .= " AND l.warehouse_id = '" . (int)$data['filter_warehouse_id'] . "'";
        }

        if (!empty($data['filter_zone_id'])) {
            $sql .= " AND l.zone_id = '" . (int)$data['filter_zone_id'] . "'";
        }

        if (!empty($data['filter_parent_location_id'])) {
            $sql .= " AND l.parent_location_id = '" . (int)$data['filter_parent_location_id'] . "'";
        }

        if (isset($data['filter_is_active']) && $data['filter_is_active'] !== '') {
            $sql .= " AND l.is_active = '" . (int)$data['filter_is_active'] . "'";
        }

        if (isset($data['filter_is_pickable']) && $data['filter_is_pickable'] !== '') {
            $sql .= " AND l.is_pickable = '" . (int)$data['filter_is_pickable'] . "'";
        }

        if (!empty($data['filter_occupancy_status'])) {
            switch ($data['filter_occupancy_status']) {
                case 'empty':
                    $sql .= " AND l.current_units = 0";
                    break;
                case 'low':
                    $sql .= " AND l.current_units > 0 AND l.current_units < (l.capacity_units * 0.5)";
                    break;
                case 'medium':
                    $sql .= " AND l.current_units >= (l.capacity_units * 0.5) AND l.current_units < (l.capacity_units * 0.8)";
                    break;
                case 'high':
                    $sql .= " AND l.current_units >= (l.capacity_units * 0.8) AND l.current_units < l.capacity_units";
                    break;
                case 'full':
                    $sql .= " AND l.current_units >= l.capacity_units";
                    break;
            }
        }

        // ترتيب النتائج
        $sort_data = array(
            'ld.name',
            'l.location_code',
            'l.location_type',
            'b.name',
            'w.name',
            'z.name',
            'l.sort_order',
            'l.date_added',
            'products_count',
            'movements_30_days',
            'usage_percentage'
        );

        if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
            $sql .= " ORDER BY " . $data['sort'];
        } else {
            $sql .= " ORDER BY l.sort_order ASC, ld.name ASC";
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
     * الحصول على إجمالي عدد المواقع
     */
    public function getTotalLocations($data = array()) {
        $sql = "
            SELECT COUNT(DISTINCT l.location_id) AS total
            FROM " . DB_PREFIX . "cod_location l
            LEFT JOIN " . DB_PREFIX . "cod_location_description ld ON (l.location_id = ld.location_id)
            WHERE ld.language_id = '" . (int)$this->config->get('config_language_id') . "'
        ";

        // تطبيق نفس الفلاتر
        if (!empty($data['filter_name'])) {
            $sql .= " AND ld.name LIKE '%" . $this->db->escape($data['filter_name']) . "%'";
        }

        if (!empty($data['filter_location_code'])) {
            $sql .= " AND l.location_code LIKE '%" . $this->db->escape($data['filter_location_code']) . "%'";
        }

        if (!empty($data['filter_location_type'])) {
            $sql .= " AND l.location_type = '" . $this->db->escape($data['filter_location_type']) . "'";
        }

        $query = $this->db->query($sql);

        return $query->row['total'];
    }

    /**
     * الحصول على موقع محدد
     */
    public function getLocation($location_id) {
        $query = $this->db->query("
            SELECT
                l.*,
                ld.name,
                ld.description,
                pl.name as parent_location_name,
                b.name as branch_name,
                w.name as warehouse_name,
                z.name as zone_name
            FROM " . DB_PREFIX . "cod_location l
            LEFT JOIN " . DB_PREFIX . "cod_location_description ld ON (l.location_id = ld.location_id)
            LEFT JOIN " . DB_PREFIX . "cod_location pl ON (l.parent_location_id = pl.location_id)
            LEFT JOIN " . DB_PREFIX . "cod_branch b ON (l.branch_id = b.branch_id)
            LEFT JOIN " . DB_PREFIX . "cod_warehouse w ON (l.warehouse_id = w.warehouse_id)
            LEFT JOIN " . DB_PREFIX . "cod_zone z ON (l.zone_id = z.zone_id)
            WHERE l.location_id = '" . (int)$location_id . "'
            AND ld.language_id = '" . (int)$this->config->get('config_language_id') . "'
        ");

        return $query->row;
    }

    /**
     * إضافة موقع جديد
     */
    public function addLocation($data) {
        // التحقق من عدم تكرار الكود
        if ($this->checkLocationCodeExists($data['location_code'])) {
            return false;
        }

        // إنشاء كود تلقائي إذا لم يتم توفيره
        if (empty($data['location_code'])) {
            $data['location_code'] = $this->generateLocationCode($data);
        }

        // إدراج الموقع الأساسي
        $this->db->query("
            INSERT INTO " . DB_PREFIX . "cod_location
            SET location_code = '" . $this->db->escape($data['location_code']) . "',
                location_type = '" . $this->db->escape($data['location_type']) . "',
                parent_location_id = " . (isset($data['parent_location_id']) && $data['parent_location_id'] ? "'" . (int)$data['parent_location_id'] . "'" : "NULL") . ",
                branch_id = " . (isset($data['branch_id']) && $data['branch_id'] ? "'" . (int)$data['branch_id'] . "'" : "NULL") . ",
                warehouse_id = " . (isset($data['warehouse_id']) && $data['warehouse_id'] ? "'" . (int)$data['warehouse_id'] . "'" : "NULL") . ",
                zone_id = " . (isset($data['zone_id']) && $data['zone_id'] ? "'" . (int)$data['zone_id'] . "'" : "NULL") . ",
                aisle = '" . $this->db->escape($data['aisle'] ?: '') . "',
                rack = '" . $this->db->escape($data['rack'] ?: '') . "',
                shelf = '" . $this->db->escape($data['shelf'] ?: '') . "',
                bin = '" . $this->db->escape($data['bin'] ?: '') . "',
                barcode = '" . $this->db->escape($data['barcode'] ?: '') . "',
                qr_code = '" . $this->db->escape($data['qr_code'] ?: '') . "',
                capacity_weight = '" . (float)($data['capacity_weight'] ?: 0) . "',
                capacity_volume = '" . (float)($data['capacity_volume'] ?: 0) . "',
                capacity_units = '" . (int)($data['capacity_units'] ?: 0) . "',
                current_weight = '" . (float)($data['current_weight'] ?: 0) . "',
                current_volume = '" . (float)($data['current_volume'] ?: 0) . "',
                current_units = '" . (int)($data['current_units'] ?: 0) . "',
                temperature_min = " . (isset($data['temperature_min']) && $data['temperature_min'] !== '' ? "'" . (float)$data['temperature_min'] . "'" : "NULL") . ",
                temperature_max = " . (isset($data['temperature_max']) && $data['temperature_max'] !== '' ? "'" . (float)$data['temperature_max'] . "'" : "NULL") . ",
                humidity_min = " . (isset($data['humidity_min']) && $data['humidity_min'] !== '' ? "'" . (float)$data['humidity_min'] . "'" : "NULL") . ",
                humidity_max = " . (isset($data['humidity_max']) && $data['humidity_max'] !== '' ? "'" . (float)$data['humidity_max'] . "'" : "NULL") . ",
                is_active = '" . (int)($data['is_active'] ?: 1) . "',
                is_pickable = '" . (int)($data['is_pickable'] ?: 1) . "',
                is_receivable = '" . (int)($data['is_receivable'] ?: 1) . "',
                is_countable = '" . (int)($data['is_countable'] ?: 1) . "',
                priority_level = '" . (int)($data['priority_level'] ?: 1) . "',
                gps_latitude = " . (isset($data['gps_latitude']) && $data['gps_latitude'] !== '' ? "'" . (float)$data['gps_latitude'] . "'" : "NULL") . ",
                gps_longitude = " . (isset($data['gps_longitude']) && $data['gps_longitude'] !== '' ? "'" . (float)$data['gps_longitude'] . "'" : "NULL") . ",
                sort_order = '" . (int)($data['sort_order'] ?: 0) . "',
                date_added = NOW(),
                date_modified = NOW()
        ");

        $location_id = $this->db->getLastId();

        // إدراج وصف الموقع
        if (isset($data['location_description'])) {
            foreach ($data['location_description'] as $language_id => $value) {
                $this->db->query("
                    INSERT INTO " . DB_PREFIX . "cod_location_description
                    SET location_id = '" . (int)$location_id . "',
                        language_id = '" . (int)$language_id . "',
                        name = '" . $this->db->escape($value['name']) . "',
                        description = '" . $this->db->escape($value['description']) . "'
                ");
            }
        }

        // إنشاء QR Code تلقائياً إذا لم يتم توفيره
        if (empty($data['qr_code'])) {
            $this->generateQRCode($location_id);
        }

        return $location_id;
    }

    /**
     * تحديث موقع
     */
    public function editLocation($location_id, $data) {
        // التحقق من عدم تكرار الكود (باستثناء الموقع الحالي)
        if ($this->checkLocationCodeExists($data['location_code'], $location_id)) {
            return false;
        }

        // تحديث الموقع الأساسي
        $this->db->query("
            UPDATE " . DB_PREFIX . "cod_location
            SET location_code = '" . $this->db->escape($data['location_code']) . "',
                location_type = '" . $this->db->escape($data['location_type']) . "',
                parent_location_id = " . (isset($data['parent_location_id']) && $data['parent_location_id'] ? "'" . (int)$data['parent_location_id'] . "'" : "NULL") . ",
                branch_id = " . (isset($data['branch_id']) && $data['branch_id'] ? "'" . (int)$data['branch_id'] . "'" : "NULL") . ",
                warehouse_id = " . (isset($data['warehouse_id']) && $data['warehouse_id'] ? "'" . (int)$data['warehouse_id'] . "'" : "NULL") . ",
                zone_id = " . (isset($data['zone_id']) && $data['zone_id'] ? "'" . (int)$data['zone_id'] . "'" : "NULL") . ",
                aisle = '" . $this->db->escape($data['aisle'] ?: '') . "',
                rack = '" . $this->db->escape($data['rack'] ?: '') . "',
                shelf = '" . $this->db->escape($data['shelf'] ?: '') . "',
                bin = '" . $this->db->escape($data['bin'] ?: '') . "',
                barcode = '" . $this->db->escape($data['barcode'] ?: '') . "',
                qr_code = '" . $this->db->escape($data['qr_code'] ?: '') . "',
                capacity_weight = '" . (float)($data['capacity_weight'] ?: 0) . "',
                capacity_volume = '" . (float)($data['capacity_volume'] ?: 0) . "',
                capacity_units = '" . (int)($data['capacity_units'] ?: 0) . "',
                temperature_min = " . (isset($data['temperature_min']) && $data['temperature_min'] !== '' ? "'" . (float)$data['temperature_min'] . "'" : "NULL") . ",
                temperature_max = " . (isset($data['temperature_max']) && $data['temperature_max'] !== '' ? "'" . (float)$data['temperature_max'] . "'" : "NULL") . ",
                humidity_min = " . (isset($data['humidity_min']) && $data['humidity_min'] !== '' ? "'" . (float)$data['humidity_min'] . "'" : "NULL") . ",
                humidity_max = " . (isset($data['humidity_max']) && $data['humidity_max'] !== '' ? "'" . (float)$data['humidity_max'] . "'" : "NULL") . ",
                is_active = '" . (int)($data['is_active'] ?: 1) . "',
                is_pickable = '" . (int)($data['is_pickable'] ?: 1) . "',
                is_receivable = '" . (int)($data['is_receivable'] ?: 1) . "',
                is_countable = '" . (int)($data['is_countable'] ?: 1) . "',
                priority_level = '" . (int)($data['priority_level'] ?: 1) . "',
                gps_latitude = " . (isset($data['gps_latitude']) && $data['gps_latitude'] !== '' ? "'" . (float)$data['gps_latitude'] . "'" : "NULL") . ",
                gps_longitude = " . (isset($data['gps_longitude']) && $data['gps_longitude'] !== '' ? "'" . (float)$data['gps_longitude'] . "'" : "NULL") . ",
                sort_order = '" . (int)($data['sort_order'] ?: 0) . "',
                date_modified = NOW()
            WHERE location_id = '" . (int)$location_id . "'
        ");

        // تحديث وصف الموقع
        $this->db->query("DELETE FROM " . DB_PREFIX . "cod_location_description WHERE location_id = '" . (int)$location_id . "'");

        if (isset($data['location_description'])) {
            foreach ($data['location_description'] as $language_id => $value) {
                $this->db->query("
                    INSERT INTO " . DB_PREFIX . "cod_location_description
                    SET location_id = '" . (int)$location_id . "',
                        language_id = '" . (int)$language_id . "',
                        name = '" . $this->db->escape($value['name']) . "',
                        description = '" . $this->db->escape($value['description']) . "'
                ");
            }
        }

        return true;
    }

    /**
     * حذف موقع
     */
    public function deleteLocation($location_id) {
        // التحقق من عدم استخدام الموقع
        $check_usage = $this->checkLocationUsage($location_id);
        if ($check_usage['in_use']) {
            return array('error' => 'لا يمكن حذف الموقع لأنه مستخدم في: ' . implode(', ', $check_usage['used_in']));
        }

        $this->db->query("DELETE FROM " . DB_PREFIX . "cod_location WHERE location_id = '" . (int)$location_id . "'");
        $this->db->query("DELETE FROM " . DB_PREFIX . "cod_location_description WHERE location_id = '" . (int)$location_id . "'");

        return array('success' => true);
    }

    /**
     * التحقق من تكرار كود الموقع
     */
    private function checkLocationCodeExists($location_code, $exclude_location_id = null) {
        $sql = "
            SELECT COUNT(*) as count
            FROM " . DB_PREFIX . "cod_location
            WHERE location_code = '" . $this->db->escape($location_code) . "'
        ";

        if ($exclude_location_id) {
            $sql .= " AND location_id != '" . (int)$exclude_location_id . "'";
        }

        $query = $this->db->query($sql);

        return $query->row['count'] > 0;
    }

    /**
     * التحقق من استخدام الموقع
     */
    private function checkLocationUsage($location_id) {
        $used_in = array();

        // التحقق من المنتجات
        $products_query = $this->db->query("
            SELECT COUNT(*) as count
            FROM " . DB_PREFIX . "cod_product_location
            WHERE location_id = '" . (int)$location_id . "'
        ");

        if ($products_query->row['count'] > 0) {
            $used_in[] = $products_query->row['count'] . ' منتج';
        }

        // التحقق من حركات المخزون
        $movements_query = $this->db->query("
            SELECT COUNT(*) as count
            FROM " . DB_PREFIX . "cod_inventory_movement
            WHERE location_id = '" . (int)$location_id . "'
        ");

        if ($movements_query->row['count'] > 0) {
            $used_in[] = $movements_query->row['count'] . ' حركة مخزون';
        }

        // التحقق من المواقع الفرعية
        $sub_locations_query = $this->db->query("
            SELECT COUNT(*) as count
            FROM " . DB_PREFIX . "cod_location
            WHERE parent_location_id = '" . (int)$location_id . "'
        ");

        if ($sub_locations_query->row['count'] > 0) {
            $used_in[] = $sub_locations_query->row['count'] . ' موقع فرعي';
        }

        return array(
            'in_use' => !empty($used_in),
            'used_in' => $used_in
        );
    }

    /**
     * إنشاء كود موقع تلقائي
     */
    private function generateLocationCode($data) {
        $prefix = '';

        // إنشاء البادئة حسب النوع
        switch ($data['location_type']) {
            case 'warehouse':
                $prefix = 'WH';
                break;
            case 'zone':
                $prefix = 'ZN';
                break;
            case 'aisle':
                $prefix = 'AI';
                break;
            case 'rack':
                $prefix = 'RK';
                break;
            case 'shelf':
                $prefix = 'SH';
                break;
            case 'bin':
                $prefix = 'BN';
                break;
            default:
                $prefix = 'LC';
        }

        // إضافة رقم الفرع إذا كان متوفراً
        if (!empty($data['branch_id'])) {
            $prefix .= str_pad($data['branch_id'], 2, '0', STR_PAD_LEFT);
        }

        // البحث عن آخر رقم مستخدم
        $query = $this->db->query("
            SELECT location_code
            FROM " . DB_PREFIX . "cod_location
            WHERE location_code LIKE '" . $prefix . "%'
            ORDER BY location_code DESC
            LIMIT 1
        ");

        $next_number = 1;
        if ($query->num_rows) {
            $last_code = $query->row['location_code'];
            $last_number = (int)substr($last_code, strlen($prefix));
            $next_number = $last_number + 1;
        }

        return $prefix . str_pad($next_number, 4, '0', STR_PAD_LEFT);
    }

    /**
     * إنشاء QR Code للموقع
     */
    private function generateQRCode($location_id) {
        $qr_code = 'LOC' . str_pad($location_id, 8, '0', STR_PAD_LEFT);

        $this->db->query("
            UPDATE " . DB_PREFIX . "cod_location
            SET qr_code = '" . $this->db->escape($qr_code) . "'
            WHERE location_id = '" . (int)$location_id . "'
        ");

        return $qr_code;
    }

    /**
     * الحصول على أنواع المواقع
     */
    public function getLocationTypes() {
        return array(
            'warehouse' => 'مستودع',
            'zone' => 'منطقة',
            'aisle' => 'ممر',
            'rack' => 'رف',
            'shelf' => 'رفة',
            'bin' => 'صندوق',
            'room' => 'غرفة',
            'floor' => 'طابق',
            'building' => 'مبنى',
            'yard' => 'ساحة',
            'dock' => 'رصيف',
            'staging' => 'منطقة تجميع'
        );
    }

    /**
     * الحصول على المواقع الرئيسية
     */
    public function getParentLocations() {
        $query = $this->db->query("
            SELECT
                l.location_id,
                ld.name,
                l.location_code,
                l.location_type
            FROM " . DB_PREFIX . "cod_location l
            LEFT JOIN " . DB_PREFIX . "cod_location_description ld ON (l.location_id = ld.location_id)
            WHERE l.parent_location_id IS NULL
            AND l.is_active = 1
            AND ld.language_id = '" . (int)$this->config->get('config_language_id') . "'
            ORDER BY l.sort_order ASC, ld.name ASC
        ");

        return $query->rows;
    }

    /**
     * الحصول على المواقع الفرعية لموقع رئيسي
     */
    public function getSubLocations($parent_location_id) {
        $query = $this->db->query("
            SELECT
                l.location_id,
                ld.name,
                l.location_code,
                l.location_type,
                l.sort_order,
                l.is_active
            FROM " . DB_PREFIX . "cod_location l
            LEFT JOIN " . DB_PREFIX . "cod_location_description ld ON (l.location_id = ld.location_id)
            WHERE l.parent_location_id = '" . (int)$parent_location_id . "'
            AND ld.language_id = '" . (int)$this->config->get('config_language_id') . "'
            ORDER BY l.sort_order ASC, ld.name ASC
        ");

        return $query->rows;
    }

    /**
     * الحصول على إحصائيات المواقع
     */
    public function getLocationStatistics() {
        $sql = "
            SELECT
                COUNT(*) as total_locations,
                COUNT(CASE WHEN l.is_active = 1 THEN 1 END) as active_locations,
                COUNT(CASE WHEN l.parent_location_id IS NULL THEN 1 END) as parent_locations,
                COUNT(DISTINCT l.location_type) as location_types,
                COUNT(DISTINCT l.branch_id) as branches_with_locations,
                COUNT(DISTINCT l.warehouse_id) as warehouses_with_locations,

                -- إحصائيات الاستخدام
                (SELECT COUNT(DISTINCT pl.product_id) FROM " . DB_PREFIX . "cod_product_location pl) as products_with_locations,
                (SELECT COUNT(*) FROM " . DB_PREFIX . "cod_inventory_movement im WHERE im.location_id IS NOT NULL) as movements_with_locations,

                -- إحصائيات السعة
                SUM(l.capacity_units) as total_capacity_units,
                SUM(l.current_units) as total_current_units,

                -- أكثر المواقع استخداماً
                (SELECT ld.name FROM " . DB_PREFIX . "cod_location l2
                 LEFT JOIN " . DB_PREFIX . "cod_location_description ld ON (l2.location_id = ld.location_id)
                 LEFT JOIN " . DB_PREFIX . "cod_product_location pl ON (l2.location_id = pl.location_id)
                 WHERE ld.language_id = '" . (int)$this->config->get('config_language_id') . "'
                 GROUP BY l2.location_id
                 ORDER BY COUNT(pl.product_id) DESC
                 LIMIT 1) as most_used_location

            FROM " . DB_PREFIX . "cod_location l
        ";

        $query = $this->db->query($sql);

        $stats = $query->row;

        // حساب نسبة الاستخدام الإجمالية
        if ($stats['total_capacity_units'] > 0) {
            $stats['overall_usage_percentage'] = round(($stats['total_current_units'] / $stats['total_capacity_units']) * 100, 2);
        } else {
            $stats['overall_usage_percentage'] = 0;
        }

        return $stats;
    }

    /**
     * الحصول على تقرير استخدام المواقع
     */
    public function getLocationUsageReport($limit = 20) {
        $query = $this->db->query("
            SELECT
                l.location_id,
                ld.name,
                l.location_code,
                l.location_type,
                COUNT(DISTINCT pl.product_id) as products_count,
                COUNT(DISTINCT im.movement_id) as movements_count,
                l.capacity_units,
                l.current_units,

                -- حساب نسبة الاستخدام
                CASE
                    WHEN l.capacity_units > 0 THEN
                        ROUND((l.current_units / l.capacity_units) * 100, 2)
                    ELSE 0
                END as usage_percentage,

                -- آخر استخدام
                (SELECT MAX(date_added) FROM " . DB_PREFIX . "cod_inventory_movement im2
                 WHERE im2.location_id = l.location_id) as last_used_date,

                -- إجمالي القيمة
                (SELECT SUM(pl2.quantity * p.cost_price) FROM " . DB_PREFIX . "cod_product_location pl2
                 LEFT JOIN " . DB_PREFIX . "cod_product p ON (pl2.product_id = p.product_id)
                 WHERE pl2.location_id = l.location_id) as total_value

            FROM " . DB_PREFIX . "cod_location l
            LEFT JOIN " . DB_PREFIX . "cod_location_description ld ON (l.location_id = ld.location_id)
            LEFT JOIN " . DB_PREFIX . "cod_product_location pl ON (l.location_id = pl.location_id)
            LEFT JOIN " . DB_PREFIX . "cod_inventory_movement im ON (l.location_id = im.location_id)
            WHERE ld.language_id = '" . (int)$this->config->get('config_language_id') . "'
            AND l.is_active = 1
            GROUP BY l.location_id
            ORDER BY products_count DESC, movements_count DESC
            LIMIT " . (int)$limit
        );

        return $query->rows;
    }

    /**
     * الحصول على الفروع
     */
    public function getBranches() {
        $query = $this->db->query("
            SELECT branch_id, name
            FROM " . DB_PREFIX . "cod_branch
            WHERE is_active = 1
            ORDER BY name ASC
        ");

        return $query->rows;
    }

    /**
     * الحصول على المستودعات
     */
    public function getWarehouses() {
        $query = $this->db->query("
            SELECT warehouse_id, name
            FROM " . DB_PREFIX . "cod_warehouse
            WHERE is_active = 1
            ORDER BY name ASC
        ");

        return $query->rows;
    }

    /**
     * الحصول على المناطق
     */
    public function getZones() {
        $query = $this->db->query("
            SELECT zone_id, name
            FROM " . DB_PREFIX . "cod_zone
            WHERE is_active = 1
            ORDER BY name ASC
        ");

        return $query->rows;
    }

    /**
     * البحث عن موقع بالباركود أو QR Code
     */
    public function findLocationByCode($code) {
        $query = $this->db->query("
            SELECT
                l.location_id,
                ld.name,
                l.location_code,
                l.location_type
            FROM " . DB_PREFIX . "cod_location l
            LEFT JOIN " . DB_PREFIX . "cod_location_description ld ON (l.location_id = ld.location_id)
            WHERE (l.barcode = '" . $this->db->escape($code) . "' OR l.qr_code = '" . $this->db->escape($code) . "')
            AND l.is_active = 1
            AND ld.language_id = '" . (int)$this->config->get('config_language_id') . "'
            LIMIT 1
        ");

        return $query->row;
    }

    /**
     * تحديث الكميات الحالية للموقع
     */
    public function updateLocationQuantities($location_id) {
        // حساب الكميات الحالية من جدول المنتجات
        $query = $this->db->query("
            SELECT
                SUM(pl.quantity) as total_units,
                SUM(pl.quantity * p.weight) as total_weight,
                SUM(pl.quantity * p.volume) as total_volume
            FROM " . DB_PREFIX . "cod_product_location pl
            LEFT JOIN " . DB_PREFIX . "cod_product p ON (pl.product_id = p.product_id)
            WHERE pl.location_id = '" . (int)$location_id . "'
        ");

        $totals = $query->row;

        // تحديث الموقع
        $this->db->query("
            UPDATE " . DB_PREFIX . "cod_location
            SET current_units = '" . (int)($totals['total_units'] ?: 0) . "',
                current_weight = '" . (float)($totals['total_weight'] ?: 0) . "',
                current_volume = '" . (float)($totals['total_volume'] ?: 0) . "',
                date_modified = NOW()
            WHERE location_id = '" . (int)$location_id . "'
        ");
    }

    /**
     * الحصول على المواقع القريبة من إحداثيات GPS
     */
    public function getNearbyLocations($latitude, $longitude, $radius_km = 10) {
        $query = $this->db->query("
            SELECT
                l.location_id,
                ld.name,
                l.location_code,
                l.gps_latitude,
                l.gps_longitude,
                (6371 * acos(cos(radians(" . (float)$latitude . "))
                * cos(radians(l.gps_latitude))
                * cos(radians(l.gps_longitude) - radians(" . (float)$longitude . "))
                + sin(radians(" . (float)$latitude . "))
                * sin(radians(l.gps_latitude)))) AS distance_km
            FROM " . DB_PREFIX . "cod_location l
            LEFT JOIN " . DB_PREFIX . "cod_location_description ld ON (l.location_id = ld.location_id)
            WHERE l.gps_latitude IS NOT NULL
            AND l.gps_longitude IS NOT NULL
            AND l.is_active = 1
            AND ld.language_id = '" . (int)$this->config->get('config_language_id') . "'
            HAVING distance_km <= " . (float)$radius_km . "
            ORDER BY distance_km ASC
        ");

        return $query->rows;
    }
}
