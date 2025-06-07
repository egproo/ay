<?php
/**
 * نموذج إدارة الباركود المتعدد المتطور (Advanced Multi-Barcode Management Model) - الجزء الأول
 *
 * الهدف: توفير نظام باركود شامل يدعم أنواع متعددة مرتبط بالوحدات والخيارات
 * الميزات: EAN, UPC, CODE128, QR, إنشاء تلقائي، طباعة، قراءة، تتبع، تحليلات
 * التكامل: مع المنتجات والوحدات والخيارات والمخزون والمبيعات والمشتريات
 *
 * @author ERP Team
 * @version 1.0
 * @since 2024
 */

class ModelInventoryBarcodeManagement extends Model {

    /**
     * الحصول على باركودات المنتج مع فلاتر متقدمة
     */
    public function getProductBarcodes($data = array()) {
        $sql = "
            SELECT
                pb.barcode_id,
                pb.product_id,
                pd.name as product_name,
                p.model,
                p.sku,
                pb.barcode_value,
                pb.barcode_type,
                pb.unit_id,
                ud.name as unit_name,
                u.symbol as unit_symbol,
                u.conversion_factor,
                pb.option_id,
                pov.name as option_name,
                pb.option_value_id,
                povd.name as option_value_name,
                pb.is_primary,
                pb.is_active,
                pb.auto_generated,
                pb.print_count,
                pb.scan_count,
                pb.last_scanned,
                pb.notes,
                pb.date_added,
                pb.date_modified,
                CASE pb.barcode_type
                    WHEN 'EAN13' THEN 'EAN-13 (أوروبي)'
                    WHEN 'EAN8' THEN 'EAN-8 (أوروبي مختصر)'
                    WHEN 'UPC' THEN 'UPC (أمريكي)'
                    WHEN 'CODE128' THEN 'Code 128 (صناعي)'
                    WHEN 'CODE39' THEN 'Code 39 (تقليدي)'
                    WHEN 'QR' THEN 'QR Code (ثنائي الأبعاد)'
                    WHEN 'DATAMATRIX' THEN 'Data Matrix (مصفوفة)'
                    WHEN 'PDF417' THEN 'PDF417 (متقدم)'
                    WHEN 'CUSTOM' THEN 'مخصص'
                    ELSE pb.barcode_type
                END as barcode_type_text,
                CASE
                    WHEN pb.is_primary = 1 THEN 'أساسي'
                    WHEN pb.unit_id IS NOT NULL THEN 'وحدة'
                    WHEN pb.option_id IS NOT NULL THEN 'خيار'
                    ELSE 'إضافي'
                END as barcode_category,
                (SELECT COUNT(*) FROM " . DB_PREFIX . "cod_barcode_scan_log bsl
                 WHERE bsl.barcode_id = pb.barcode_id
                 AND DATE(bsl.scan_date) = CURDATE()) as today_scans,
                (SELECT COUNT(*) FROM " . DB_PREFIX . "cod_barcode_print_log bpl
                 WHERE bpl.barcode_id = pb.barcode_id
                 AND DATE(bpl.print_date) = CURDATE()) as today_prints
            FROM " . DB_PREFIX . "cod_product_barcode pb
            LEFT JOIN " . DB_PREFIX . "cod_product p ON (pb.product_id = p.product_id)
            LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id)
            LEFT JOIN " . DB_PREFIX . "cod_unit u ON (pb.unit_id = u.unit_id)
            LEFT JOIN " . DB_PREFIX . "cod_unit_description ud ON (u.unit_id = ud.unit_id)
            LEFT JOIN " . DB_PREFIX . "product_option_value pov ON (pb.option_id = pov.option_id)
            LEFT JOIN " . DB_PREFIX . "product_option_value_description povd ON (pb.option_value_id = povd.option_value_id)
            WHERE pd.language_id = '" . (int)$this->config->get('config_language_id') . "'
            AND ud.language_id = '" . (int)$this->config->get('config_language_id') . "'
            AND povd.language_id = '" . (int)$this->config->get('config_language_id') . "'
        ";

        // تطبيق الفلاتر
        if (!empty($data['filter_product_id'])) {
            $sql .= " AND pb.product_id = '" . (int)$data['filter_product_id'] . "'";
        }

        if (!empty($data['filter_product_name'])) {
            $sql .= " AND pd.name LIKE '%" . $this->db->escape($data['filter_product_name']) . "%'";
        }

        if (!empty($data['filter_barcode_value'])) {
            $sql .= " AND pb.barcode_value LIKE '%" . $this->db->escape($data['filter_barcode_value']) . "%'";
        }

        if (!empty($data['filter_barcode_type'])) {
            $sql .= " AND pb.barcode_type = '" . $this->db->escape($data['filter_barcode_type']) . "'";
        }

        if (!empty($data['filter_unit_id'])) {
            $sql .= " AND pb.unit_id = '" . (int)$data['filter_unit_id'] . "'";
        }

        if (!empty($data['filter_option_id'])) {
            $sql .= " AND pb.option_id = '" . (int)$data['filter_option_id'] . "'";
        }

        if (isset($data['filter_is_primary']) && $data['filter_is_primary'] !== '') {
            $sql .= " AND pb.is_primary = '" . (int)$data['filter_is_primary'] . "'";
        }

        if (isset($data['filter_is_active']) && $data['filter_is_active'] !== '') {
            $sql .= " AND pb.is_active = '" . (int)$data['filter_is_active'] . "'";
        }

        if (isset($data['filter_auto_generated']) && $data['filter_auto_generated'] !== '') {
            $sql .= " AND pb.auto_generated = '" . (int)$data['filter_auto_generated'] . "'";
        }

        if (!empty($data['filter_date_from'])) {
            $sql .= " AND DATE(pb.date_added) >= '" . $this->db->escape($data['filter_date_from']) . "'";
        }

        if (!empty($data['filter_date_to'])) {
            $sql .= " AND DATE(pb.date_added) <= '" . $this->db->escape($data['filter_date_to']) . "'";
        }

        // ترتيب النتائج
        $sort_data = array(
            'pd.name',
            'pb.barcode_value',
            'pb.barcode_type',
            'ud.name',
            'pb.is_primary',
            'pb.is_active',
            'pb.scan_count',
            'pb.print_count',
            'pb.date_added'
        );

        if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
            $sql .= " ORDER BY " . $data['sort'];
        } else {
            $sql .= " ORDER BY pd.name ASC, pb.is_primary DESC, pb.barcode_type ASC";
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
     * الحصول على إجمالي عدد الباركودات
     */
    public function getTotalProductBarcodes($data = array()) {
        $sql = "
            SELECT COUNT(*) AS total
            FROM " . DB_PREFIX . "cod_product_barcode pb
            LEFT JOIN " . DB_PREFIX . "cod_product p ON (pb.product_id = p.product_id)
            LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id)
            WHERE pd.language_id = '" . (int)$this->config->get('config_language_id') . "'
        ";

        // تطبيق نفس الفلاتر
        if (!empty($data['filter_product_id'])) {
            $sql .= " AND pb.product_id = '" . (int)$data['filter_product_id'] . "'";
        }

        if (!empty($data['filter_barcode_value'])) {
            $sql .= " AND pb.barcode_value LIKE '%" . $this->db->escape($data['filter_barcode_value']) . "%'";
        }

        if (!empty($data['filter_barcode_type'])) {
            $sql .= " AND pb.barcode_type = '" . $this->db->escape($data['filter_barcode_type']) . "'";
        }

        $query = $this->db->query($sql);

        return $query->row['total'];
    }

    /**
     * الحصول على باركود محدد
     */
    public function getProductBarcode($barcode_id) {
        $query = $this->db->query("
            SELECT
                pb.*,
                pd.name as product_name,
                p.model,
                p.sku,
                ud.name as unit_name,
                u.symbol as unit_symbol,
                pov.name as option_name,
                povd.name as option_value_name
            FROM " . DB_PREFIX . "cod_product_barcode pb
            LEFT JOIN " . DB_PREFIX . "cod_product p ON (pb.product_id = p.product_id)
            LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id)
            LEFT JOIN " . DB_PREFIX . "cod_unit u ON (pb.unit_id = u.unit_id)
            LEFT JOIN " . DB_PREFIX . "cod_unit_description ud ON (u.unit_id = ud.unit_id)
            LEFT JOIN " . DB_PREFIX . "product_option_value pov ON (pb.option_id = pov.option_id)
            LEFT JOIN " . DB_PREFIX . "product_option_value_description povd ON (pb.option_value_id = povd.option_value_id)
            WHERE pb.barcode_id = '" . (int)$barcode_id . "'
            AND pd.language_id = '" . (int)$this->config->get('config_language_id') . "'
            AND ud.language_id = '" . (int)$this->config->get('config_language_id') . "'
            AND povd.language_id = '" . (int)$this->config->get('config_language_id') . "'
        ");

        return $query->row;
    }

    /**
     * البحث عن باركود بالقيمة
     */
    public function findBarcodeByValue($barcode_value) {
        $query = $this->db->query("
            SELECT
                pb.*,
                pd.name as product_name,
                p.model,
                p.sku,
                p.price,
                ud.name as unit_name,
                u.symbol as unit_symbol,
                u.conversion_factor,
                pov.name as option_name,
                povd.name as option_value_name,
                pi.quantity as stock_quantity
            FROM " . DB_PREFIX . "cod_product_barcode pb
            LEFT JOIN " . DB_PREFIX . "cod_product p ON (pb.product_id = p.product_id)
            LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id)
            LEFT JOIN " . DB_PREFIX . "cod_unit u ON (pb.unit_id = u.unit_id)
            LEFT JOIN " . DB_PREFIX . "cod_unit_description ud ON (u.unit_id = ud.unit_id)
            LEFT JOIN " . DB_PREFIX . "product_option_value pov ON (pb.option_id = pov.option_id)
            LEFT JOIN " . DB_PREFIX . "product_option_value_description povd ON (pb.option_value_id = povd.option_value_id)
            LEFT JOIN " . DB_PREFIX . "cod_product_inventory pi ON (p.product_id = pi.product_id)
            WHERE pb.barcode_value = '" . $this->db->escape($barcode_value) . "'
            AND pb.is_active = 1
            AND pd.language_id = '" . (int)$this->config->get('config_language_id') . "'
            AND ud.language_id = '" . (int)$this->config->get('config_language_id') . "'
            AND povd.language_id = '" . (int)$this->config->get('config_language_id') . "'
            LIMIT 1
        ");

        if ($query->num_rows) {
            // تسجيل عملية المسح
            $this->logBarcodeScan($query->row['barcode_id'], 'search');
            return $query->row;
        }

        return false;
    }

    /**
     * إضافة باركود جديد
     */
    public function addProductBarcode($data) {
        // التحقق من عدم تكرار الباركود
        if ($this->barcodeExists($data['barcode_value'])) {
            return false;
        }

        // إذا كان باركود أساسي، إلغاء الأساسي الحالي
        if (!empty($data['is_primary'])) {
            $this->db->query("
                UPDATE " . DB_PREFIX . "cod_product_barcode
                SET is_primary = 0
                WHERE product_id = '" . (int)$data['product_id'] . "'
            ");
        }

        $this->db->query("
            INSERT INTO " . DB_PREFIX . "cod_product_barcode
            SET product_id = '" . (int)$data['product_id'] . "',
                barcode_value = '" . $this->db->escape($data['barcode_value']) . "',
                barcode_type = '" . $this->db->escape($data['barcode_type']) . "',
                unit_id = " . (!empty($data['unit_id']) ? "'" . (int)$data['unit_id'] . "'" : "NULL") . ",
                option_id = " . (!empty($data['option_id']) ? "'" . (int)$data['option_id'] . "'" : "NULL") . ",
                option_value_id = " . (!empty($data['option_value_id']) ? "'" . (int)$data['option_value_id'] . "'" : "NULL") . ",
                is_primary = '" . (int)(!empty($data['is_primary'])) . "',
                is_active = '" . (int)(!empty($data['is_active']) ? $data['is_active'] : 1) . "',
                auto_generated = '" . (int)(!empty($data['auto_generated'])) . "',
                notes = '" . $this->db->escape($data['notes']) . "',
                date_added = NOW(),
                date_modified = NOW()
        ");

        $barcode_id = $this->db->getLastId();

        // تسجيل في سجل الباركود
        $this->logBarcodeActivity($barcode_id, 'created', 'تم إنشاء باركود جديد');

        return $barcode_id;
    }

    /**
     * تحديث باركود
     */
    public function editProductBarcode($barcode_id, $data) {
        // التحقق من عدم تكرار الباركود (باستثناء الحالي)
        if ($this->barcodeExists($data['barcode_value'], $barcode_id)) {
            return false;
        }

        // إذا كان باركود أساسي، إلغاء الأساسي الحالي
        if (!empty($data['is_primary'])) {
            $barcode_info = $this->getProductBarcode($barcode_id);
            $this->db->query("
                UPDATE " . DB_PREFIX . "cod_product_barcode
                SET is_primary = 0
                WHERE product_id = '" . (int)$barcode_info['product_id'] . "'
                AND barcode_id != '" . (int)$barcode_id . "'
            ");
        }

        $this->db->query("
            UPDATE " . DB_PREFIX . "cod_product_barcode
            SET barcode_value = '" . $this->db->escape($data['barcode_value']) . "',
                barcode_type = '" . $this->db->escape($data['barcode_type']) . "',
                unit_id = " . (!empty($data['unit_id']) ? "'" . (int)$data['unit_id'] . "'" : "NULL") . ",
                option_id = " . (!empty($data['option_id']) ? "'" . (int)$data['option_id'] . "'" : "NULL") . ",
                option_value_id = " . (!empty($data['option_value_id']) ? "'" . (int)$data['option_value_id'] . "'" : "NULL") . ",
                is_primary = '" . (int)(!empty($data['is_primary'])) . "',
                is_active = '" . (int)(!empty($data['is_active']) ? $data['is_active'] : 1) . "',
                notes = '" . $this->db->escape($data['notes']) . "',
                date_modified = NOW()
            WHERE barcode_id = '" . (int)$barcode_id . "'
        ");

        // تسجيل في سجل الباركود
        $this->logBarcodeActivity($barcode_id, 'updated', 'تم تحديث الباركود');

        return true;
    }

    /**
     * حذف باركود
     */
    public function deleteProductBarcode($barcode_id) {
        // تسجيل في سجل الباركود قبل الحذف
        $this->logBarcodeActivity($barcode_id, 'deleted', 'تم حذف الباركود');

        $this->db->query("DELETE FROM " . DB_PREFIX . "cod_product_barcode WHERE barcode_id = '" . (int)$barcode_id . "'");
        $this->db->query("DELETE FROM " . DB_PREFIX . "cod_barcode_scan_log WHERE barcode_id = '" . (int)$barcode_id . "'");
        $this->db->query("DELETE FROM " . DB_PREFIX . "cod_barcode_print_log WHERE barcode_id = '" . (int)$barcode_id . "'");
    }

    /**
     * التحقق من وجود باركود
     */
    public function barcodeExists($barcode_value, $exclude_id = 0) {
        $sql = "
            SELECT barcode_id
            FROM " . DB_PREFIX . "cod_product_barcode
            WHERE barcode_value = '" . $this->db->escape($barcode_value) . "'
        ";

        if ($exclude_id) {
            $sql .= " AND barcode_id != '" . (int)$exclude_id . "'";
        }

        $query = $this->db->query($sql);

        return $query->num_rows > 0;
    }

    /**
     * توليد باركود تلقائي
     */
    public function generateBarcode($product_id, $barcode_type = 'EAN13', $unit_id = null, $option_id = null, $option_value_id = null) {
        $barcode_value = '';

        switch ($barcode_type) {
            case 'EAN13':
                $barcode_value = $this->generateEAN13($product_id, $unit_id, $option_id);
                break;
            case 'EAN8':
                $barcode_value = $this->generateEAN8($product_id, $unit_id, $option_id);
                break;
            case 'UPC':
                $barcode_value = $this->generateUPC($product_id, $unit_id, $option_id);
                break;
            case 'CODE128':
                $barcode_value = $this->generateCODE128($product_id, $unit_id, $option_id);
                break;
            case 'QR':
                $barcode_value = $this->generateQR($product_id, $unit_id, $option_id, $option_value_id);
                break;
            default:
                $barcode_value = $this->generateCustom($product_id, $unit_id, $option_id);
                break;
        }

        // التأكد من عدم التكرار
        $counter = 1;
        $original_value = $barcode_value;
        while ($this->barcodeExists($barcode_value)) {
            $barcode_value = $original_value . str_pad($counter, 2, '0', STR_PAD_LEFT);
            $counter++;
        }

        return $barcode_value;
    }

    /**
     * توليد EAN-13
     */
    private function generateEAN13($product_id, $unit_id = null, $option_id = null) {
        // كود الشركة (3 أرقام) + كود المنتج (6 أرقام) + كود الوحدة/الخيار (3 أرقام) + رقم التحقق (1 رقم)
        $company_code = str_pad($this->config->get('config_barcode_company_code') ?: '123', 3, '0', STR_PAD_LEFT);
        $product_code = str_pad($product_id, 6, '0', STR_PAD_LEFT);
        $variant_code = str_pad(($unit_id ?: 0) + ($option_id ?: 0), 3, '0', STR_PAD_LEFT);

        $base = $company_code . $product_code . $variant_code;
        $check_digit = $this->calculateEANCheckDigit($base);

        return $base . $check_digit;
    }

    /**
     * حساب رقم التحقق لـ EAN
     */
    private function calculateEANCheckDigit($code) {
        $sum = 0;
        for ($i = 0; $i < strlen($code); $i++) {
            $digit = (int)$code[$i];
            $sum += ($i % 2 == 0) ? $digit : $digit * 3;
        }

        $remainder = $sum % 10;
        return $remainder == 0 ? 0 : 10 - $remainder;
    }

    /**
     * توليد EAN-8
     */
    private function generateEAN8($product_id, $unit_id = null, $option_id = null) {
        // نسخة مختصرة من EAN-13
        $company_code = str_pad($this->config->get('config_barcode_company_code') ?: '12', 2, '0', STR_PAD_LEFT);
        $product_code = str_pad($product_id, 4, '0', STR_PAD_LEFT);
        $variant_code = str_pad(($unit_id ?: 0) + ($option_id ?: 0), 1, '0', STR_PAD_LEFT);

        $base = $company_code . $product_code . $variant_code;
        $check_digit = $this->calculateEANCheckDigit($base);

        return $base . $check_digit;
    }

    /**
     * توليد UPC
     */
    private function generateUPC($product_id, $unit_id = null, $option_id = null) {
        // مشابه لـ EAN-13 لكن بتنسيق أمريكي
        $company_code = str_pad($this->config->get('config_barcode_upc_company_code') ?: '12345', 5, '0', STR_PAD_LEFT);
        $product_code = str_pad($product_id, 5, '0', STR_PAD_LEFT);
        $variant_code = str_pad(($unit_id ?: 0) + ($option_id ?: 0), 1, '0', STR_PAD_LEFT);

        $base = $company_code . $product_code . $variant_code;
        $check_digit = $this->calculateEANCheckDigit($base);

        return $base . $check_digit;
    }

    /**
     * توليد CODE128
     */
    private function generateCODE128($product_id, $unit_id = null, $option_id = null) {
        // تنسيق أبجدي رقمي للاستخدام الصناعي
        $prefix = 'PRD';
        $product_code = str_pad($product_id, 6, '0', STR_PAD_LEFT);
        $unit_suffix = $unit_id ? 'U' . str_pad($unit_id, 2, '0', STR_PAD_LEFT) : '';
        $option_suffix = $option_id ? 'O' . str_pad($option_id, 2, '0', STR_PAD_LEFT) : '';

        return $prefix . $product_code . $unit_suffix . $option_suffix;
    }

    /**
     * توليد QR Code
     */
    private function generateQR($product_id, $unit_id = null, $option_id = null, $option_value_id = null) {
        // JSON data for QR code
        $data = array(
            'product_id' => $product_id,
            'type' => 'product'
        );

        if ($unit_id) {
            $data['unit_id'] = $unit_id;
        }

        if ($option_id) {
            $data['option_id'] = $option_id;
        }

        if ($option_value_id) {
            $data['option_value_id'] = $option_value_id;
        }

        return base64_encode(json_encode($data));
    }

    /**
     * توليد باركود مخصص
     */
    private function generateCustom($product_id, $unit_id = null, $option_id = null) {
        $timestamp = time();
        $random = mt_rand(100, 999);

        return 'CUST' . $product_id . ($unit_id ?: '0') . ($option_id ?: '0') . $timestamp . $random;
    }

    /**
     * تسجيل عملية مسح الباركود
     */
    public function logBarcodeScan($barcode_id, $scan_type = 'manual', $location = '', $notes = '') {
        $this->db->query("
            INSERT INTO " . DB_PREFIX . "cod_barcode_scan_log
            SET barcode_id = '" . (int)$barcode_id . "',
                user_id = '" . (int)$this->user->getId() . "',
                scan_type = '" . $this->db->escape($scan_type) . "',
                location = '" . $this->db->escape($location) . "',
                notes = '" . $this->db->escape($notes) . "',
                scan_date = NOW()
        ");

        // تحديث عداد المسح
        $this->db->query("
            UPDATE " . DB_PREFIX . "cod_product_barcode
            SET scan_count = scan_count + 1,
                last_scanned = NOW()
            WHERE barcode_id = '" . (int)$barcode_id . "'
        ");
    }

    /**
     * تسجيل عملية طباعة الباركود
     */
    public function logBarcodePrint($barcode_id, $print_type = 'single', $quantity = 1, $printer = '', $notes = '') {
        $this->db->query("
            INSERT INTO " . DB_PREFIX . "cod_barcode_print_log
            SET barcode_id = '" . (int)$barcode_id . "',
                user_id = '" . (int)$this->user->getId() . "',
                print_type = '" . $this->db->escape($print_type) . "',
                quantity = '" . (int)$quantity . "',
                printer = '" . $this->db->escape($printer) . "',
                notes = '" . $this->db->escape($notes) . "',
                print_date = NOW()
        ");

        // تحديث عداد الطباعة
        $this->db->query("
            UPDATE " . DB_PREFIX . "cod_product_barcode
            SET print_count = print_count + " . (int)$quantity . "
            WHERE barcode_id = '" . (int)$barcode_id . "'
        ");
    }

    /**
     * تسجيل نشاط الباركود
     */
    private function logBarcodeActivity($barcode_id, $activity_type, $description = '') {
        $this->db->query("
            INSERT INTO " . DB_PREFIX . "cod_barcode_activity_log
            SET barcode_id = '" . (int)$barcode_id . "',
                user_id = '" . (int)$this->user->getId() . "',
                activity_type = '" . $this->db->escape($activity_type) . "',
                description = '" . $this->db->escape($description) . "',
                activity_date = NOW()
        ");
    }

    /**
     * الحصول على إحصائيات الباركود
     */
    public function getBarcodeStatistics($data = array()) {
        $sql = "
            SELECT
                COUNT(*) as total_barcodes,
                SUM(CASE WHEN pb.is_active = 1 THEN 1 ELSE 0 END) as active_barcodes,
                SUM(CASE WHEN pb.is_primary = 1 THEN 1 ELSE 0 END) as primary_barcodes,
                SUM(CASE WHEN pb.auto_generated = 1 THEN 1 ELSE 0 END) as auto_generated_barcodes,
                SUM(pb.scan_count) as total_scans,
                SUM(pb.print_count) as total_prints,
                AVG(pb.scan_count) as avg_scans_per_barcode,
                AVG(pb.print_count) as avg_prints_per_barcode,
                COUNT(DISTINCT pb.product_id) as products_with_barcodes,
                COUNT(DISTINCT pb.barcode_type) as barcode_types_used
            FROM " . DB_PREFIX . "cod_product_barcode pb
            WHERE 1=1
        ";

        if (!empty($data['filter_date_from'])) {
            $sql .= " AND DATE(pb.date_added) >= '" . $this->db->escape($data['filter_date_from']) . "'";
        }

        if (!empty($data['filter_date_to'])) {
            $sql .= " AND DATE(pb.date_added) <= '" . $this->db->escape($data['filter_date_to']) . "'";
        }

        $query = $this->db->query($sql);

        return $query->row;
    }

    /**
     * الحصول على إحصائيات حسب النوع
     */
    public function getBarcodeTypeStatistics($data = array()) {
        $sql = "
            SELECT
                pb.barcode_type,
                COUNT(*) as count,
                SUM(pb.scan_count) as total_scans,
                SUM(pb.print_count) as total_prints,
                AVG(pb.scan_count) as avg_scans,
                AVG(pb.print_count) as avg_prints,
                CASE pb.barcode_type
                    WHEN 'EAN13' THEN 'EAN-13 (أوروبي)'
                    WHEN 'EAN8' THEN 'EAN-8 (أوروبي مختصر)'
                    WHEN 'UPC' THEN 'UPC (أمريكي)'
                    WHEN 'CODE128' THEN 'Code 128 (صناعي)'
                    WHEN 'CODE39' THEN 'Code 39 (تقليدي)'
                    WHEN 'QR' THEN 'QR Code (ثنائي الأبعاد)'
                    WHEN 'DATAMATRIX' THEN 'Data Matrix (مصفوفة)'
                    WHEN 'PDF417' THEN 'PDF417 (متقدم)'
                    WHEN 'CUSTOM' THEN 'مخصص'
                    ELSE pb.barcode_type
                END as barcode_type_text
            FROM " . DB_PREFIX . "cod_product_barcode pb
            WHERE pb.is_active = 1
        ";

        if (!empty($data['filter_date_from'])) {
            $sql .= " AND DATE(pb.date_added) >= '" . $this->db->escape($data['filter_date_from']) . "'";
        }

        if (!empty($data['filter_date_to'])) {
            $sql .= " AND DATE(pb.date_added) <= '" . $this->db->escape($data['filter_date_to']) . "'";
        }

        $sql .= " GROUP BY pb.barcode_type ORDER BY count DESC";

        $query = $this->db->query($sql);

        return $query->rows;
    }

    /**
     * الحصول على أكثر الباركودات استخداماً
     */
    public function getMostUsedBarcodes($limit = 10) {
        $query = $this->db->query("
            SELECT
                pb.barcode_id,
                pb.barcode_value,
                pb.barcode_type,
                pb.scan_count,
                pb.print_count,
                pd.name as product_name,
                p.model,
                ud.name as unit_name,
                (pb.scan_count + pb.print_count) as total_usage
            FROM " . DB_PREFIX . "cod_product_barcode pb
            LEFT JOIN " . DB_PREFIX . "cod_product p ON (pb.product_id = p.product_id)
            LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id)
            LEFT JOIN " . DB_PREFIX . "cod_unit u ON (pb.unit_id = u.unit_id)
            LEFT JOIN " . DB_PREFIX . "cod_unit_description ud ON (u.unit_id = ud.unit_id)
            WHERE pb.is_active = 1
            AND pd.language_id = '" . (int)$this->config->get('config_language_id') . "'
            AND ud.language_id = '" . (int)$this->config->get('config_language_id') . "'
            ORDER BY total_usage DESC
            LIMIT " . (int)$limit
        );

        return $query->rows;
    }

    /**
     * الحصول على سجل المسح
     */
    public function getScanLog($barcode_id, $limit = 50) {
        $query = $this->db->query("
            SELECT
                bsl.*,
                CONCAT(u.firstname, ' ', u.lastname) as user_name
            FROM " . DB_PREFIX . "cod_barcode_scan_log bsl
            LEFT JOIN " . DB_PREFIX . "user u ON (bsl.user_id = u.user_id)
            WHERE bsl.barcode_id = '" . (int)$barcode_id . "'
            ORDER BY bsl.scan_date DESC
            LIMIT " . (int)$limit
        );

        return $query->rows;
    }

    /**
     * الحصول على سجل الطباعة
     */
    public function getPrintLog($barcode_id, $limit = 50) {
        $query = $this->db->query("
            SELECT
                bpl.*,
                CONCAT(u.firstname, ' ', u.lastname) as user_name
            FROM " . DB_PREFIX . "cod_barcode_print_log bpl
            LEFT JOIN " . DB_PREFIX . "user u ON (bpl.user_id = u.user_id)
            WHERE bpl.barcode_id = '" . (int)$barcode_id . "'
            ORDER BY bpl.print_date DESC
            LIMIT " . (int)$limit
        );

        return $query->rows;
    }

    /**
     * الحصول على أنواع الباركود المتاحة
     */
    public function getBarcodeTypes() {
        return array(
            array('value' => 'EAN13', 'text' => 'EAN-13 (أوروبي)', 'description' => 'الأكثر شيوعاً في أوروبا - 13 رقم'),
            array('value' => 'EAN8', 'text' => 'EAN-8 (أوروبي مختصر)', 'description' => 'نسخة مختصرة من EAN-13 - 8 أرقام'),
            array('value' => 'UPC', 'text' => 'UPC (أمريكي)', 'description' => 'الأكثر شيوعاً في أمريكا الشمالية - 12 رقم'),
            array('value' => 'CODE128', 'text' => 'Code 128 (صناعي)', 'description' => 'مرن ويدعم الأحرف والأرقام'),
            array('value' => 'CODE39', 'text' => 'Code 39 (تقليدي)', 'description' => 'تقليدي وبسيط - أحرف وأرقام'),
            array('value' => 'QR', 'text' => 'QR Code (ثنائي الأبعاد)', 'description' => 'يحمل معلومات كثيرة - يقرأ بالهاتف'),
            array('value' => 'DATAMATRIX', 'text' => 'Data Matrix (مصفوفة)', 'description' => 'مضغوط وعالي الكثافة'),
            array('value' => 'PDF417', 'text' => 'PDF417 (متقدم)', 'description' => 'ثنائي الأبعاد متقدم'),
            array('value' => 'CUSTOM', 'text' => 'مخصص', 'description' => 'تنسيق مخصص حسب الحاجة')
        );
    }

    /**
     * الحصول على باركودات المنتج
     */
    public function getProductBarcodesList($product_id) {
        $query = $this->db->query("
            SELECT
                pb.*,
                ud.name as unit_name,
                u.symbol as unit_symbol,
                pov.name as option_name,
                povd.name as option_value_name
            FROM " . DB_PREFIX . "cod_product_barcode pb
            LEFT JOIN " . DB_PREFIX . "cod_unit u ON (pb.unit_id = u.unit_id)
            LEFT JOIN " . DB_PREFIX . "cod_unit_description ud ON (u.unit_id = ud.unit_id)
            LEFT JOIN " . DB_PREFIX . "product_option_value pov ON (pb.option_id = pov.option_id)
            LEFT JOIN " . DB_PREFIX . "product_option_value_description povd ON (pb.option_value_id = povd.option_value_id)
            WHERE pb.product_id = '" . (int)$product_id . "'
            AND pb.is_active = 1
            AND ud.language_id = '" . (int)$this->config->get('config_language_id') . "'
            AND povd.language_id = '" . (int)$this->config->get('config_language_id') . "'
            ORDER BY pb.is_primary DESC, pb.barcode_type ASC
        ");

        return $query->rows;
    }

    /**
     * إنشاء باركودات تلقائية للمنتج
     */
    public function generateProductBarcodes($product_id, $types = array('EAN13'), $include_units = true, $include_options = true) {
        $generated = array();

        // الحصول على معلومات المنتج
        $this->load->model('catalog/product');
        $product_info = $this->model_catalog_product->getProduct($product_id);

        if (!$product_info) {
            return $generated;
        }

        // إنشاء باركود أساسي
        foreach ($types as $type) {
            $barcode_value = $this->generateBarcode($product_id, $type);

            $barcode_data = array(
                'product_id' => $product_id,
                'barcode_value' => $barcode_value,
                'barcode_type' => $type,
                'is_primary' => count($generated) == 0 ? 1 : 0,
                'is_active' => 1,
                'auto_generated' => 1,
                'notes' => 'تم إنشاؤه تلقائياً'
            );

            $barcode_id = $this->addProductBarcode($barcode_data);
            if ($barcode_id) {
                $generated[] = $barcode_id;
            }
        }

        // إنشاء باركودات للوحدات
        if ($include_units) {
            $this->load->model('inventory/unit');
            $units = $this->model_inventory_unit->getProductUnits($product_id);

            foreach ($units as $unit) {
                foreach ($types as $type) {
                    $barcode_value = $this->generateBarcode($product_id, $type, $unit['unit_id']);

                    $barcode_data = array(
                        'product_id' => $product_id,
                        'barcode_value' => $barcode_value,
                        'barcode_type' => $type,
                        'unit_id' => $unit['unit_id'],
                        'is_primary' => 0,
                        'is_active' => 1,
                        'auto_generated' => 1,
                        'notes' => 'تم إنشاؤه تلقائياً للوحدة: ' . $unit['name']
                    );

                    $barcode_id = $this->addProductBarcode($barcode_data);
                    if ($barcode_id) {
                        $generated[] = $barcode_id;
                    }
                }
            }
        }

        // إنشاء باركودات للخيارات
        if ($include_options) {
            $this->load->model('catalog/product');
            $options = $this->model_catalog_product->getProductOptions($product_id);

            foreach ($options as $option) {
                foreach ($option['product_option_value'] as $option_value) {
                    foreach ($types as $type) {
                        $barcode_value = $this->generateBarcode($product_id, $type, null, $option['option_id'], $option_value['option_value_id']);

                        $barcode_data = array(
                            'product_id' => $product_id,
                            'barcode_value' => $barcode_value,
                            'barcode_type' => $type,
                            'option_id' => $option['option_id'],
                            'option_value_id' => $option_value['option_value_id'],
                            'is_primary' => 0,
                            'is_active' => 1,
                            'auto_generated' => 1,
                            'notes' => 'تم إنشاؤه تلقائياً للخيار: ' . $option['name'] . ' - ' . $option_value['name']
                        );

                        $barcode_id = $this->addProductBarcode($barcode_data);
                        if ($barcode_id) {
                            $generated[] = $barcode_id;
                        }
                    }
                }
            }
        }

        return $generated;
    }

    /**
     * التحقق من صحة الباركود
     */
    public function validateBarcode($barcode_value, $barcode_type) {
        switch ($barcode_type) {
            case 'EAN13':
                return $this->validateEAN13($barcode_value);
            case 'EAN8':
                return $this->validateEAN8($barcode_value);
            case 'UPC':
                return $this->validateUPC($barcode_value);
            case 'CODE128':
                return $this->validateCODE128($barcode_value);
            case 'QR':
                return $this->validateQR($barcode_value);
            default:
                return true; // للأنواع المخصصة
        }
    }

    /**
     * التحقق من صحة EAN-13
     */
    private function validateEAN13($barcode) {
        if (strlen($barcode) != 13 || !ctype_digit($barcode)) {
            return false;
        }

        $check_digit = substr($barcode, -1);
        $base = substr($barcode, 0, 12);
        $calculated_check = $this->calculateEANCheckDigit($base);

        return $check_digit == $calculated_check;
    }

    /**
     * التحقق من صحة EAN-8
     */
    private function validateEAN8($barcode) {
        if (strlen($barcode) != 8 || !ctype_digit($barcode)) {
            return false;
        }

        $check_digit = substr($barcode, -1);
        $base = substr($barcode, 0, 7);
        $calculated_check = $this->calculateEANCheckDigit($base);

        return $check_digit == $calculated_check;
    }

    /**
     * التحقق من صحة UPC
     */
    private function validateUPC($barcode) {
        if (strlen($barcode) != 12 || !ctype_digit($barcode)) {
            return false;
        }

        $check_digit = substr($barcode, -1);
        $base = substr($barcode, 0, 11);
        $calculated_check = $this->calculateEANCheckDigit($base);

        return $check_digit == $calculated_check;
    }

    /**
     * التحقق من صحة CODE128
     */
    private function validateCODE128($barcode) {
        // CODE128 يمكن أن يحتوي على أحرف وأرقام
        return strlen($barcode) >= 1 && strlen($barcode) <= 48;
    }

    /**
     * التحقق من صحة QR
     */
    private function validateQR($barcode) {
        // QR يمكن أن يحتوي على أي محتوى
        $decoded = base64_decode($barcode, true);
        if ($decoded === false) {
            return false;
        }

        $data = json_decode($decoded, true);
        return json_last_error() === JSON_ERROR_NONE;
    }

    /**
     * تصدير البيانات للإكسل
     */
    public function exportToExcel($data = array()) {
        // إزالة حدود الصفحات للتصدير
        unset($data['start']);
        unset($data['limit']);

        return $this->getProductBarcodes($data);
    }
}
