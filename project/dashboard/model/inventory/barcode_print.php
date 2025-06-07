<?php
/**
 * نموذج طباعة الباركود المتطور (Advanced Barcode Printing Model)
 *
 * الهدف: توفير نظام طباعة باركود شامل ومتطور للمنتجات
 * الميزات: أنواع باركود متعددة، قوالب طباعة، طباعة مجمعة، تخصيص التصميم
 * التكامل: مع المنتجات والوحدات والخيارات والمخزون
 *
 * @author ERP Team
 * @version 1.0
 * @since 2024
 */

class ModelInventoryBarcodePrint extends Model {

    /**
     * الحصول على المنتجات للطباعة مع فلاتر متقدمة
     */
    public function getProductsForPrinting($data = array()) {
        $sql = "
            SELECT
                p.product_id,
                pd.name as product_name,
                p.model,
                p.sku,
                p.upc,
                p.ean,
                p.jan,
                p.isbn,
                p.mpn,
                p.price,
                p.weight,
                p.length,
                p.width,
                p.height,
                p.status,
                p.date_added,
                p.date_modified,
                m.name as manufacturer_name,
                (SELECT pi.quantity FROM " . DB_PREFIX . "cod_product_inventory pi
                 WHERE pi.product_id = p.product_id
                 AND pi.branch_id = '" . (int)$this->config->get('config_branch_id') . "'
                 LIMIT 1) as current_stock,
                (SELECT GROUP_CONCAT(CONCAT(pb.barcode_type, ':', pb.barcode_value) SEPARATOR '|')
                 FROM " . DB_PREFIX . "cod_product_barcode pb
                 WHERE pb.product_id = p.product_id) as barcodes,
                (SELECT COUNT(*) FROM " . DB_PREFIX . "cod_product_barcode pb
                 WHERE pb.product_id = p.product_id) as barcode_count
            FROM " . DB_PREFIX . "cod_product p
            LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id)
            LEFT JOIN " . DB_PREFIX . "manufacturer m ON (p.manufacturer_id = m.manufacturer_id)
            WHERE pd.language_id = '" . (int)$this->config->get('config_language_id') . "'
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

        if (!empty($data['filter_barcode'])) {
            $sql .= " AND (p.upc LIKE '%" . $this->db->escape($data['filter_barcode']) . "%'
                          OR p.ean LIKE '%" . $this->db->escape($data['filter_barcode']) . "%'
                          OR EXISTS (SELECT 1 FROM " . DB_PREFIX . "cod_product_barcode pb
                                     WHERE pb.product_id = p.product_id
                                     AND pb.barcode_value LIKE '%" . $this->db->escape($data['filter_barcode']) . "%'))";
        }

        if (!empty($data['filter_manufacturer_id'])) {
            $sql .= " AND p.manufacturer_id = '" . (int)$data['filter_manufacturer_id'] . "'";
        }

        if (!empty($data['filter_category_id'])) {
            $sql .= " AND EXISTS (SELECT 1 FROM " . DB_PREFIX . "product_to_category p2c
                                  WHERE p2c.product_id = p.product_id
                                  AND p2c.category_id = '" . (int)$data['filter_category_id'] . "')";
        }

        if (isset($data['filter_status']) && $data['filter_status'] !== '') {
            $sql .= " AND p.status = '" . (int)$data['filter_status'] . "'";
        }

        if (!empty($data['filter_stock_status'])) {
            if ($data['filter_stock_status'] == 'in_stock') {
                $sql .= " AND (SELECT pi.quantity FROM " . DB_PREFIX . "cod_product_inventory pi
                              WHERE pi.product_id = p.product_id
                              AND pi.branch_id = '" . (int)$this->config->get('config_branch_id') . "') > 0";
            } elseif ($data['filter_stock_status'] == 'out_of_stock') {
                $sql .= " AND (SELECT pi.quantity FROM " . DB_PREFIX . "cod_product_inventory pi
                              WHERE pi.product_id = p.product_id
                              AND pi.branch_id = '" . (int)$this->config->get('config_branch_id') . "') <= 0";
            }
        }

        // ترتيب النتائج
        $sort_data = array(
            'pd.name',
            'p.model',
            'p.sku',
            'p.price',
            'current_stock',
            'barcode_count',
            'p.date_added'
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
    public function getTotalProductsForPrinting($data = array()) {
        $sql = "
            SELECT COUNT(*) AS total
            FROM " . DB_PREFIX . "cod_product p
            LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id)
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

        $query = $this->db->query($sql);

        return $query->row['total'];
    }

    /**
     * الحصول على باركودات منتج محدد
     */
    public function getProductBarcodes($product_id) {
        $query = $this->db->query("
            SELECT
                pb.*,
                u.name as unit_name,
                u.symbol as unit_symbol
            FROM " . DB_PREFIX . "cod_product_barcode pb
            LEFT JOIN " . DB_PREFIX . "cod_unit u ON (pb.unit_id = u.unit_id)
            WHERE pb.product_id = '" . (int)$product_id . "'
            ORDER BY pb.is_primary DESC, pb.barcode_type ASC
        ");

        return $query->rows;
    }

    /**
     * الحصول على قوالب الطباعة
     */
    public function getPrintTemplates() {
        $query = $this->db->query("
            SELECT * FROM " . DB_PREFIX . "cod_barcode_template
            WHERE status = 1
            ORDER BY is_default DESC, name ASC
        ");

        return $query->rows;
    }

    /**
     * الحصول على قالب طباعة محدد
     */
    public function getPrintTemplate($template_id) {
        $query = $this->db->query("
            SELECT * FROM " . DB_PREFIX . "cod_barcode_template
            WHERE template_id = '" . (int)$template_id . "'
        ");

        return $query->row;
    }

    /**
     * إنشاء قالب طباعة جديد
     */
    public function addPrintTemplate($data) {
        $this->db->query("
            INSERT INTO " . DB_PREFIX . "cod_barcode_template
            SET name = '" . $this->db->escape($data['name']) . "',
                description = '" . $this->db->escape($data['description']) . "',
                template_type = '" . $this->db->escape($data['template_type']) . "',
                paper_size = '" . $this->db->escape($data['paper_size']) . "',
                orientation = '" . $this->db->escape($data['orientation']) . "',
                margin_top = '" . (float)$data['margin_top'] . "',
                margin_bottom = '" . (float)$data['margin_bottom'] . "',
                margin_left = '" . (float)$data['margin_left'] . "',
                margin_right = '" . (float)$data['margin_right'] . "',
                label_width = '" . (float)$data['label_width'] . "',
                label_height = '" . (float)$data['label_height'] . "',
                labels_per_row = '" . (int)$data['labels_per_row'] . "',
                labels_per_column = '" . (int)$data['labels_per_column'] . "',
                horizontal_spacing = '" . (float)$data['horizontal_spacing'] . "',
                vertical_spacing = '" . (float)$data['vertical_spacing'] . "',
                font_family = '" . $this->db->escape($data['font_family']) . "',
                font_size = '" . (int)$data['font_size'] . "',
                show_product_name = '" . (int)$data['show_product_name'] . "',
                show_model = '" . (int)$data['show_model'] . "',
                show_sku = '" . (int)$data['show_sku'] . "',
                show_price = '" . (int)$data['show_price'] . "',
                show_barcode = '" . (int)$data['show_barcode'] . "',
                barcode_type = '" . $this->db->escape($data['barcode_type']) . "',
                barcode_height = '" . (int)$data['barcode_height'] . "',
                custom_css = '" . $this->db->escape($data['custom_css']) . "',
                is_default = '" . (int)$data['is_default'] . "',
                status = '" . (int)$data['status'] . "',
                date_added = NOW(),
                date_modified = NOW()
        ");

        $template_id = $this->db->getLastId();

        // إذا كان هذا القالب افتراضي، قم بإلغاء الافتراضية من القوالب الأخرى
        if ($data['is_default']) {
            $this->db->query("
                UPDATE " . DB_PREFIX . "cod_barcode_template
                SET is_default = 0
                WHERE template_id != '" . (int)$template_id . "'
            ");
        }

        return $template_id;
    }

    /**
     * تحديث قالب طباعة
     */
    public function editPrintTemplate($template_id, $data) {
        $this->db->query("
            UPDATE " . DB_PREFIX . "cod_barcode_template
            SET name = '" . $this->db->escape($data['name']) . "',
                description = '" . $this->db->escape($data['description']) . "',
                template_type = '" . $this->db->escape($data['template_type']) . "',
                paper_size = '" . $this->db->escape($data['paper_size']) . "',
                orientation = '" . $this->db->escape($data['orientation']) . "',
                margin_top = '" . (float)$data['margin_top'] . "',
                margin_bottom = '" . (float)$data['margin_bottom'] . "',
                margin_left = '" . (float)$data['margin_left'] . "',
                margin_right = '" . (float)$data['margin_right'] . "',
                label_width = '" . (float)$data['label_width'] . "',
                label_height = '" . (float)$data['label_height'] . "',
                labels_per_row = '" . (int)$data['labels_per_row'] . "',
                labels_per_column = '" . (int)$data['labels_per_column'] . "',
                horizontal_spacing = '" . (float)$data['horizontal_spacing'] . "',
                vertical_spacing = '" . (float)$data['vertical_spacing'] . "',
                font_family = '" . $this->db->escape($data['font_family']) . "',
                font_size = '" . (int)$data['font_size'] . "',
                show_product_name = '" . (int)$data['show_product_name'] . "',
                show_model = '" . (int)$data['show_model'] . "',
                show_sku = '" . (int)$data['show_sku'] . "',
                show_price = '" . (int)$data['show_price'] . "',
                show_barcode = '" . (int)$data['show_barcode'] . "',
                barcode_type = '" . $this->db->escape($data['barcode_type']) . "',
                barcode_height = '" . (int)$data['barcode_height'] . "',
                custom_css = '" . $this->db->escape($data['custom_css']) . "',
                is_default = '" . (int)$data['is_default'] . "',
                status = '" . (int)$data['status'] . "',
                date_modified = NOW()
            WHERE template_id = '" . (int)$template_id . "'
        ");

        // إذا كان هذا القالب افتراضي، قم بإلغاء الافتراضية من القوالب الأخرى
        if ($data['is_default']) {
            $this->db->query("
                UPDATE " . DB_PREFIX . "cod_barcode_template
                SET is_default = 0
                WHERE template_id != '" . (int)$template_id . "'
            ");
        }
    }

    /**
     * حذف قالب طباعة
     */
    public function deletePrintTemplate($template_id) {
        $this->db->query("DELETE FROM " . DB_PREFIX . "cod_barcode_template WHERE template_id = '" . (int)$template_id . "'");
    }

    /**
     * الحصول على أنواع الباركود المدعومة
     */
    public function getSupportedBarcodeTypes() {
        return array(
            'CODE128' => 'Code 128',
            'CODE39' => 'Code 39',
            'EAN13' => 'EAN-13',
            'EAN8' => 'EAN-8',
            'UPC' => 'UPC-A',
            'QRCODE' => 'QR Code',
            'DATAMATRIX' => 'Data Matrix',
            'PDF417' => 'PDF417'
        );
    }

    /**
     * الحصول على أحجام الورق المدعومة
     */
    public function getSupportedPaperSizes() {
        return array(
            'A4' => 'A4 (210 x 297 mm)',
            'A5' => 'A5 (148 x 210 mm)',
            'LETTER' => 'Letter (8.5 x 11 inch)',
            'LEGAL' => 'Legal (8.5 x 14 inch)',
            'CUSTOM' => 'مخصص'
        );
    }

    /**
     * توليد باركود للمنتج
     */
    public function generateBarcode($product_id, $barcode_type = 'CODE128', $barcode_value = null) {
        if (!$barcode_value) {
            // توليد باركود تلقائي
            $barcode_value = $this->generateBarcodeValue($product_id, $barcode_type);
        }

        // التحقق من عدم تكرار الباركود
        $existing = $this->db->query("
            SELECT barcode_id FROM " . DB_PREFIX . "cod_product_barcode
            WHERE barcode_value = '" . $this->db->escape($barcode_value) . "'
            AND product_id != '" . (int)$product_id . "'
        ");

        if ($existing->num_rows > 0) {
            return false; // الباركود موجود مسبقاً
        }

        // إضافة الباركود
        $this->db->query("
            INSERT INTO " . DB_PREFIX . "cod_product_barcode
            SET product_id = '" . (int)$product_id . "',
                barcode_type = '" . $this->db->escape($barcode_type) . "',
                barcode_value = '" . $this->db->escape($barcode_value) . "',
                is_primary = 0,
                date_added = NOW()
        ");

        return $barcode_value;
    }

    /**
     * توليد قيمة باركود تلقائية
     */
    private function generateBarcodeValue($product_id, $barcode_type) {
        switch ($barcode_type) {
            case 'EAN13':
                return $this->generateEAN13($product_id);
            case 'EAN8':
                return $this->generateEAN8($product_id);
            case 'UPC':
                return $this->generateUPC($product_id);
            case 'CODE128':
            case 'CODE39':
            default:
                return str_pad($product_id, 10, '0', STR_PAD_LEFT);
        }
    }

    /**
     * توليد EAN-13
     */
    private function generateEAN13($product_id) {
        $prefix = $this->config->get('config_barcode_prefix') ?: '123'; // 3 أرقام
        $product_code = str_pad($product_id, 9, '0', STR_PAD_LEFT); // 9 أرقام
        $partial = $prefix . $product_code; // 12 رقم

        // حساب رقم التحقق
        $checksum = $this->calculateEAN13Checksum($partial);

        return $partial . $checksum;
    }

    /**
     * حساب رقم التحقق لـ EAN-13
     */
    private function calculateEAN13Checksum($code) {
        $sum = 0;
        for ($i = 0; $i < 12; $i++) {
            $digit = (int)$code[$i];
            $sum += ($i % 2 == 0) ? $digit : $digit * 3;
        }
        return (10 - ($sum % 10)) % 10;
    }

    /**
     * توليد EAN-8
     */
    private function generateEAN8($product_id) {
        $prefix = $this->config->get('config_barcode_prefix_short') ?: '12'; // 2 رقم
        $product_code = str_pad($product_id, 5, '0', STR_PAD_LEFT); // 5 أرقام
        $partial = $prefix . $product_code; // 7 أرقام

        // حساب رقم التحقق
        $checksum = $this->calculateEAN8Checksum($partial);

        return $partial . $checksum;
    }

    /**
     * حساب رقم التحقق لـ EAN-8
     */
    private function calculateEAN8Checksum($code) {
        $sum = 0;
        for ($i = 0; $i < 7; $i++) {
            $digit = (int)$code[$i];
            $sum += ($i % 2 == 0) ? $digit * 3 : $digit;
        }
        return (10 - ($sum % 10)) % 10;
    }

    /**
     * توليد UPC-A
     */
    private function generateUPC($product_id) {
        $prefix = $this->config->get('config_upc_prefix') ?: '12345'; // 5 أرقام
        $product_code = str_pad($product_id, 6, '0', STR_PAD_LEFT); // 6 أرقام
        $partial = $prefix . $product_code; // 11 رقم

        // حساب رقم التحقق
        $checksum = $this->calculateUPCChecksum($partial);

        return $partial . $checksum;
    }

    /**
     * حساب رقم التحقق لـ UPC-A
     */
    private function calculateUPCChecksum($code) {
        $sum = 0;
        for ($i = 0; $i < 11; $i++) {
            $digit = (int)$code[$i];
            $sum += ($i % 2 == 0) ? $digit * 3 : $digit;
        }
        return (10 - ($sum % 10)) % 10;
    }

    /**
     * الحصول على الشركات المصنعة
     */
    public function getManufacturers() {
        $query = $this->db->query("
            SELECT * FROM " . DB_PREFIX . "manufacturer
            WHERE status = 1
            ORDER BY name ASC
        ");

        return $query->rows;
    }

    /**
     * الحصول على التصنيفات
     */
    public function getCategories() {
        $query = $this->db->query("
            SELECT c.category_id, cd.name, c.parent_id
            FROM " . DB_PREFIX . "category c
            LEFT JOIN " . DB_PREFIX . "category_description cd ON (c.category_id = cd.category_id)
            WHERE c.status = 1
            AND cd.language_id = '" . (int)$this->config->get('config_language_id') . "'
            ORDER BY cd.name ASC
        ");

        return $query->rows;
    }

    /**
     * حفظ إعدادات الطباعة
     */
    public function savePrintSettings($data) {
        $this->db->query("
            INSERT INTO " . DB_PREFIX . "cod_barcode_print_job
            SET template_id = '" . (int)$data['template_id'] . "',
                product_ids = '" . $this->db->escape($data['product_ids']) . "',
                copies_per_product = '" . (int)$data['copies_per_product'] . "',
                total_labels = '" . (int)$data['total_labels'] . "',
                user_id = '" . (int)$this->user->getId() . "',
                print_settings = '" . $this->db->escape(json_encode($data)) . "',
                status = 'pending',
                date_added = NOW()
        ");

        return $this->db->getLastId();
    }

    /**
     * الحصول على تاريخ الطباعة
     */
    public function getPrintHistory($data = array()) {
        $sql = "
            SELECT
                pj.*,
                bt.name as template_name,
                CONCAT(u.firstname, ' ', u.lastname) as user_name
            FROM " . DB_PREFIX . "cod_barcode_print_job pj
            LEFT JOIN " . DB_PREFIX . "cod_barcode_template bt ON (pj.template_id = bt.template_id)
            LEFT JOIN " . DB_PREFIX . "user u ON (pj.user_id = u.user_id)
            WHERE 1=1
        ";

        if (!empty($data['filter_date_from'])) {
            $sql .= " AND DATE(pj.date_added) >= '" . $this->db->escape($data['filter_date_from']) . "'";
        }

        if (!empty($data['filter_date_to'])) {
            $sql .= " AND DATE(pj.date_added) <= '" . $this->db->escape($data['filter_date_to']) . "'";
        }

        $sql .= " ORDER BY pj.date_added DESC";

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
     * إنشاء SKU تلقائي
     */
    public function generateSKU($product_id) {
        // إنشاء SKU بناءً على معرف المنتج والوقت الحالي
        $prefix = $this->config->get('barcode_sku_prefix') ?: 'SKU';
        $sku = $prefix . str_pad($product_id, 6, '0', STR_PAD_LEFT) . date('ymd');

        // التحقق من عدم وجود SKU مشابه
        $query = $this->db->query("SELECT COUNT(*) as total FROM " . DB_PREFIX . "product WHERE sku = '" . $this->db->escape($sku) . "'");

        if ($query->row['total'] > 0) {
            // إضافة رقم تسلسلي إذا كان SKU موجود
            $counter = 1;
            do {
                $new_sku = $sku . sprintf('%02d', $counter);
                $query = $this->db->query("SELECT COUNT(*) as total FROM " . DB_PREFIX . "product WHERE sku = '" . $this->db->escape($new_sku) . "'");
                $counter++;
            } while ($query->row['total'] > 0);

            $sku = $new_sku;
        }

        return $sku;
    }

    /**
     * التحقق من صحة تنسيق الباركود
     */
    public function validateBarcodeFormat($barcode, $type) {
        switch ($type) {
            case 'code128':
                return preg_match('/^[0-9A-Za-z\-\.\$\/\+\%\s]+$/', $barcode) && strlen($barcode) <= 48;
            case 'code39':
                return preg_match('/^[0-9A-Z\-\.\$\/\+\%\s]+$/', $barcode) && strlen($barcode) <= 43;
            case 'ean13':
                return preg_match('/^[0-9]{13}$/', $barcode);
            case 'ean8':
                return preg_match('/^[0-9]{8}$/', $barcode);
            case 'upc':
                return preg_match('/^[0-9]{12}$/', $barcode);
            case 'qrcode':
                return strlen($barcode) <= 4296;
            default:
                return false;
        }
    }

    /**
     * نسخ قالب
     */
    public function duplicateTemplate($template_id) {
        $template_info = $this->getTemplate($template_id);

        if ($template_info) {
            unset($template_info['template_id']);
            $template_info['name'] .= ' (نسخة)';
            $template_info['is_default'] = 0;
            $template_info['created_date'] = date('Y-m-d H:i:s');

            return $this->addTemplate($template_info);
        }

        return false;
    }

    /**
     * تعيين قالب افتراضي
     */
    public function setDefaultTemplate($template_id) {
        // إزالة الافتراضي من جميع القوالب
        $this->db->query("UPDATE " . DB_PREFIX . "barcode_templates SET is_default = 0");

        // تعيين القالب المحدد كافتراضي
        $this->db->query("UPDATE " . DB_PREFIX . "barcode_templates SET is_default = 1 WHERE template_id = '" . (int)$template_id . "'");

        return true;
    }

    /**
     * الحصول على القوالب الأخيرة
     */
    public function getRecentTemplates($limit = 5) {
        $query = $this->db->query("
            SELECT * FROM " . DB_PREFIX . "barcode_templates
            ORDER BY last_used DESC, created_date DESC
            LIMIT " . (int)$limit
        );

        return $query->rows;
    }

    /**
     * إحصائيات الباركود
     */
    public function getBarcodeStatistics() {
        $stats = array();

        // إجمالي المنتجات
        $query = $this->db->query("SELECT COUNT(*) as total FROM " . DB_PREFIX . "product WHERE status = 1");
        $stats['total_products'] = $query->row['total'];

        // المنتجات التي لديها باركود
        $query = $this->db->query("SELECT COUNT(*) as total FROM " . DB_PREFIX . "product WHERE status = 1 AND (sku IS NOT NULL AND sku != '')");
        $stats['products_with_barcode'] = $query->row['total'];

        // المنتجات بدون باركود
        $stats['products_without_barcode'] = $stats['total_products'] - $stats['products_with_barcode'];

        // نسبة التغطية
        $stats['barcode_coverage'] = $stats['total_products'] > 0 ? round(($stats['products_with_barcode'] / $stats['total_products']) * 100, 2) : 0;

        // إجمالي القوالب
        $query = $this->db->query("SELECT COUNT(*) as total FROM " . DB_PREFIX . "barcode_templates");
        $stats['total_templates'] = $query->row['total'];

        // القالب الافتراضي
        $query = $this->db->query("SELECT name FROM " . DB_PREFIX . "barcode_templates WHERE is_default = 1");
        $stats['default_template'] = $query->num_rows ? $query->row['name'] : 'لا يوجد';

        // إحصائيات الطباعة (آخر 30 يوم)
        $query = $this->db->query("
            SELECT COUNT(*) as total
            FROM " . DB_PREFIX . "barcode_print_log
            WHERE print_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        ");
        $stats['prints_last_30_days'] = $query->num_rows ? $query->row['total'] : 0;

        return $stats;
    }

    /**
     * إنشاء صفحة اختبار
     */
    public function generateTestPage($test_data, $settings) {
        // إنشاء PDF للاختبار
        require_once(DIR_SYSTEM . 'library/tcpdf/tcpdf.php');

        $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);

        // إعدادات PDF
        $pdf->SetCreator('AYM ERP');
        $pdf->SetAuthor('AYM ERP');
        $pdf->SetTitle('Barcode Test Page');
        $pdf->SetSubject('Barcode Test');

        $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
        $pdf->SetMargins(20, 20, 20);
        $pdf->SetAutoPageBreak(TRUE, 20);
        $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

        $pdf->AddPage();

        // عنوان الصفحة
        $pdf->SetFont('dejavusans', 'B', 16);
        $pdf->Cell(0, 10, 'صفحة اختبار الباركود', 0, 1, 'C');
        $pdf->Ln(10);

        // معلومات الاختبار
        $pdf->SetFont('dejavusans', '', 12);
        $pdf->Cell(0, 8, 'تاريخ الاختبار: ' . date('Y-m-d H:i:s'), 0, 1);
        $pdf->Cell(0, 8, 'نوع الباركود: ' . ($settings['barcode_type'] ?? 'Code 128'), 0, 1);
        $pdf->Cell(0, 8, 'حجم الورق: ' . ($settings['paper_size'] ?? 'A4'), 0, 1);
        $pdf->Ln(10);

        // إنشاء باركود تجريبي
        $barcode_data = $test_data['sku'];
        $barcode_type = $settings['barcode_type'] ?? 'code128';

        // رسم الباركود
        $pdf->write1DBarcode($barcode_data, strtoupper($barcode_type), 50, 80, 100, 30, 0.4, array('position' => 'S', 'border' => true, 'padding' => 4, 'fgcolor' => array(0,0,0), 'bgcolor' => array(255,255,255)));

        // معلومات المنتج التجريبي
        $pdf->SetXY(50, 120);
        $pdf->SetFont('dejavusans', 'B', 10);
        $pdf->Cell(100, 6, $test_data['name'], 0, 1, 'C');
        $pdf->SetX(50);
        $pdf->SetFont('dejavusans', '', 9);
        $pdf->Cell(100, 5, 'SKU: ' . $test_data['sku'], 0, 1, 'C');
        $pdf->SetX(50);
        $pdf->Cell(100, 5, 'السعر: ' . $test_data['price'], 0, 1, 'C');

        return $pdf->Output('', 'S');
    }

    /**
     * تسجيل عملية طباعة
     */
    public function logPrint($product_ids, $quantities, $settings, $user_id) {
        $total_labels = array_sum($quantities);

        $this->db->query("
            INSERT INTO " . DB_PREFIX . "barcode_print_log
            SET user_id = '" . (int)$user_id . "',
                product_count = '" . count($product_ids) . "',
                label_count = '" . (int)$total_labels . "',
                settings = '" . $this->db->escape(json_encode($settings)) . "',
                print_date = NOW()
        ");

        return $this->db->getLastId();
    }

    /**
     * الحصول على سجل الطباعة
     */
    public function getPrintLog($data = array()) {
        $sql = "
            SELECT pl.*, u.firstname, u.lastname
            FROM " . DB_PREFIX . "barcode_print_log pl
            LEFT JOIN " . DB_PREFIX . "user u ON pl.user_id = u.user_id
            WHERE 1=1
        ";

        if (!empty($data['filter_user_id'])) {
            $sql .= " AND pl.user_id = '" . (int)$data['filter_user_id'] . "'";
        }

        if (!empty($data['filter_date_from'])) {
            $sql .= " AND DATE(pl.print_date) >= '" . $this->db->escape($data['filter_date_from']) . "'";
        }

        if (!empty($data['filter_date_to'])) {
            $sql .= " AND DATE(pl.print_date) <= '" . $this->db->escape($data['filter_date_to']) . "'";
        }

        $sort_data = array(
            'pl.print_date',
            'pl.label_count',
            'u.firstname'
        );

        if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
            $sql .= " ORDER BY " . $data['sort'];
        } else {
            $sql .= " ORDER BY pl.print_date";
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
     * تحديث آخر استخدام للقالب
     */
    public function updateTemplateUsage($template_id) {
        $this->db->query("
            UPDATE " . DB_PREFIX . "barcode_templates
            SET last_used = NOW(),
                usage_count = usage_count + 1
            WHERE template_id = '" . (int)$template_id . "'
        ");
    }

    /**
     * تنظيف القوالب القديمة
     */
    public function cleanupOldTemplates($days = 90) {
        $this->db->query("
            DELETE FROM " . DB_PREFIX . "barcode_templates
            WHERE is_default = 0
            AND (last_used IS NULL OR last_used < DATE_SUB(NOW(), INTERVAL " . (int)$days . " DAY))
            AND created_date < DATE_SUB(NOW(), INTERVAL " . (int)$days . " DAY)
        ");

        return $this->db->countAffected();
    }

    /**
     * تصدير إعدادات النظام
     */
    public function exportSystemSettings() {
        $settings = array();

        // إعدادات الباركود
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "setting WHERE `key` LIKE 'barcode_%'");
        foreach ($query->rows as $setting) {
            $settings[$setting['key']] = $setting['value'];
        }

        // القوالب
        $settings['templates'] = $this->getTemplates();

        return json_encode($settings, JSON_PRETTY_PRINT);
    }

    /**
     * استيراد إعدادات النظام
     */
    public function importSystemSettings($settings_json) {
        $settings = json_decode($settings_json, true);

        if (!$settings) {
            throw new Exception('ملف الإعدادات غير صحيح');
        }

        // استيراد الإعدادات
        foreach ($settings as $key => $value) {
            if (strpos($key, 'barcode_') === 0) {
                $this->db->query("
                    INSERT INTO " . DB_PREFIX . "setting
                    SET store_id = 0,
                        `code` = 'barcode',
                        `key` = '" . $this->db->escape($key) . "',
                        `value` = '" . $this->db->escape($value) . "'
                    ON DUPLICATE KEY UPDATE `value` = '" . $this->db->escape($value) . "'
                ");
            }
        }

        // استيراد القوالب
        if (isset($settings['templates'])) {
            foreach ($settings['templates'] as $template) {
                unset($template['template_id']);
                $template['name'] .= ' (مستورد)';
                $this->addTemplate($template);
            }
        }

        return true;
    }
}
