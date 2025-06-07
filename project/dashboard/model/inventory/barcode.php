<?php
/**
 * نموذج إدارة الباركود المتقدم (Advanced Barcode Management Model)
 * 
 * الهدف: توفير نظام باركود متطور مع دعم أنواع متعددة وربط بالوحدات والخيارات
 * الميزات: 6 أنواع باركود، ربط بالوحدات والخيارات، توليد تلقائي، طباعة
 * التكامل: مع المنتجات والوحدات والخيارات ونقاط البيع
 * 
 * @author ERP Team
 * @version 1.0
 * @since 2024
 */

class ModelInventoryBarcode extends Model {
    
    /**
     * أنواع الباركود المدعومة
     */
    private $barcode_types = array(
        'EAN13'   => 'EAN-13 (13 digits)',
        'EAN8'    => 'EAN-8 (8 digits)',
        'UPC'     => 'UPC (12 digits)',
        'CODE128' => 'Code 128',
        'CODE39'  => 'Code 39',
        'ISBN'    => 'ISBN (10/13 digits)'
    );
    
    /**
     * إضافة باركود جديد
     */
    public function addBarcode($data) {
        $this->db->query("
            INSERT INTO " . DB_PREFIX . "cod_product_barcode SET 
            product_id = '" . (int)$data['product_id'] . "',
            barcode = '" . $this->db->escape($data['barcode']) . "',
            barcode_type = '" . $this->db->escape($data['barcode_type']) . "',
            unit_id = '" . (int)$data['unit_id'] . "',
            option_id = '" . (int)$data['option_id'] . "',
            option_value_id = '" . (int)$data['option_value_id'] . "',
            is_primary = '" . (int)$data['is_primary'] . "',
            status = '" . (int)$data['status'] . "',
            date_added = NOW(),
            date_modified = NOW()
        ");
        
        $barcode_id = $this->db->getLastId();
        
        // إذا كان هذا الباركود أساسي، إلغاء الأساسية من الباركودات الأخرى للمنتج نفسه
        if ($data['is_primary']) {
            $this->db->query("
                UPDATE " . DB_PREFIX . "cod_product_barcode 
                SET is_primary = 0 
                WHERE product_id = '" . (int)$data['product_id'] . "' 
                AND barcode_id != '" . (int)$barcode_id . "'
            ");
        }
        
        return $barcode_id;
    }
    
    /**
     * تعديل باركود موجود
     */
    public function editBarcode($barcode_id, $data) {
        $this->db->query("
            UPDATE " . DB_PREFIX . "cod_product_barcode SET 
            barcode = '" . $this->db->escape($data['barcode']) . "',
            barcode_type = '" . $this->db->escape($data['barcode_type']) . "',
            unit_id = '" . (int)$data['unit_id'] . "',
            option_id = '" . (int)$data['option_id'] . "',
            option_value_id = '" . (int)$data['option_value_id'] . "',
            is_primary = '" . (int)$data['is_primary'] . "',
            status = '" . (int)$data['status'] . "',
            date_modified = NOW()
            WHERE barcode_id = '" . (int)$barcode_id . "'
        ");
        
        // إذا كان هذا الباركود أساسي، إلغاء الأساسية من الباركودات الأخرى
        if ($data['is_primary']) {
            $barcode_info = $this->getBarcode($barcode_id);
            if ($barcode_info) {
                $this->db->query("
                    UPDATE " . DB_PREFIX . "cod_product_barcode 
                    SET is_primary = 0 
                    WHERE product_id = '" . (int)$barcode_info['product_id'] . "' 
                    AND barcode_id != '" . (int)$barcode_id . "'
                ");
            }
        }
    }
    
    /**
     * حذف باركود
     */
    public function deleteBarcode($barcode_id) {
        $this->db->query("DELETE FROM " . DB_PREFIX . "cod_product_barcode WHERE barcode_id = '" . (int)$barcode_id . "'");
    }
    
    /**
     * الحصول على باركود واحد
     */
    public function getBarcode($barcode_id) {
        $query = $this->db->query("
            SELECT pb.*, p.model, pd.name as product_name,
            u.name as unit_name, o.name as option_name, ov.name as option_value_name
            FROM " . DB_PREFIX . "cod_product_barcode pb
            LEFT JOIN " . DB_PREFIX . "cod_product p ON (pb.product_id = p.product_id)
            LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id)
            LEFT JOIN " . DB_PREFIX . "cod_unit_description u ON (pb.unit_id = u.unit_id)
            LEFT JOIN " . DB_PREFIX . "option_description o ON (pb.option_id = o.option_id)
            LEFT JOIN " . DB_PREFIX . "option_value_description ov ON (pb.option_value_id = ov.option_value_id)
            WHERE pb.barcode_id = '" . (int)$barcode_id . "'
            AND pd.language_id = '" . (int)$this->config->get('config_language_id') . "'
            AND u.language_id = '" . (int)$this->config->get('config_language_id') . "'
            AND o.language_id = '" . (int)$this->config->get('config_language_id') . "'
            AND ov.language_id = '" . (int)$this->config->get('config_language_id') . "'
        ");
        
        return $query->row;
    }
    
    /**
     * البحث عن منتج بالباركود
     */
    public function getProductByBarcode($barcode) {
        $query = $this->db->query("
            SELECT pb.*, p.*, pd.name as product_name,
            u.name as unit_name, u.symbol as unit_symbol,
            o.name as option_name, ov.name as option_value_name
            FROM " . DB_PREFIX . "cod_product_barcode pb
            LEFT JOIN " . DB_PREFIX . "cod_product p ON (pb.product_id = p.product_id)
            LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id)
            LEFT JOIN " . DB_PREFIX . "cod_unit_description u ON (pb.unit_id = u.unit_id)
            LEFT JOIN " . DB_PREFIX . "option_description o ON (pb.option_id = o.option_id)
            LEFT JOIN " . DB_PREFIX . "option_value_description ov ON (pb.option_value_id = ov.option_value_id)
            WHERE pb.barcode = '" . $this->db->escape($barcode) . "'
            AND pb.status = 1
            AND p.status = 1
            AND pd.language_id = '" . (int)$this->config->get('config_language_id') . "'
            AND u.language_id = '" . (int)$this->config->get('config_language_id') . "'
            AND o.language_id = '" . (int)$this->config->get('config_language_id') . "'
            AND ov.language_id = '" . (int)$this->config->get('config_language_id') . "'
            LIMIT 1
        ");
        
        return $query->row;
    }
    
    /**
     * الحصول على باركودات منتج
     */
    public function getProductBarcodes($product_id) {
        $query = $this->db->query("
            SELECT pb.*, 
            u.name as unit_name, u.symbol as unit_symbol,
            o.name as option_name, ov.name as option_value_name
            FROM " . DB_PREFIX . "cod_product_barcode pb
            LEFT JOIN " . DB_PREFIX . "cod_unit_description u ON (pb.unit_id = u.unit_id)
            LEFT JOIN " . DB_PREFIX . "option_description o ON (pb.option_id = o.option_id)
            LEFT JOIN " . DB_PREFIX . "option_value_description ov ON (pb.option_value_id = ov.option_value_id)
            WHERE pb.product_id = '" . (int)$product_id . "'
            AND u.language_id = '" . (int)$this->config->get('config_language_id') . "'
            AND o.language_id = '" . (int)$this->config->get('config_language_id') . "'
            AND ov.language_id = '" . (int)$this->config->get('config_language_id') . "'
            ORDER BY pb.is_primary DESC, pb.barcode_id ASC
        ");
        
        return $query->rows;
    }
    
    /**
     * الحصول على قائمة الباركودات
     */
    public function getBarcodes($data = array()) {
        $sql = "
            SELECT pb.barcode_id, pb.barcode, pb.barcode_type, pb.is_primary, pb.status,
            p.model, pd.name as product_name,
            u.name as unit_name, o.name as option_name, ov.name as option_value_name
            FROM " . DB_PREFIX . "cod_product_barcode pb
            LEFT JOIN " . DB_PREFIX . "cod_product p ON (pb.product_id = p.product_id)
            LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id)
            LEFT JOIN " . DB_PREFIX . "cod_unit_description u ON (pb.unit_id = u.unit_id)
            LEFT JOIN " . DB_PREFIX . "option_description o ON (pb.option_id = o.option_id)
            LEFT JOIN " . DB_PREFIX . "option_value_description ov ON (pb.option_value_id = ov.option_value_id)
            WHERE pd.language_id = '" . (int)$this->config->get('config_language_id') . "'
            AND u.language_id = '" . (int)$this->config->get('config_language_id') . "'
            AND o.language_id = '" . (int)$this->config->get('config_language_id') . "'
            AND ov.language_id = '" . (int)$this->config->get('config_language_id') . "'
        ";
        
        if (!empty($data['filter_barcode'])) {
            $sql .= " AND pb.barcode LIKE '%" . $this->db->escape($data['filter_barcode']) . "%'";
        }
        
        if (!empty($data['filter_product'])) {
            $sql .= " AND (pd.name LIKE '%" . $this->db->escape($data['filter_product']) . "%' OR p.model LIKE '%" . $this->db->escape($data['filter_product']) . "%')";
        }
        
        if (!empty($data['filter_type'])) {
            $sql .= " AND pb.barcode_type = '" . $this->db->escape($data['filter_type']) . "'";
        }
        
        if (isset($data['filter_status']) && $data['filter_status'] !== '') {
            $sql .= " AND pb.status = '" . (int)$data['filter_status'] . "'";
        }
        
        $sort_data = array(
            'pb.barcode',
            'pd.name',
            'p.model',
            'pb.barcode_type',
            'pb.is_primary',
            'pb.status'
        );
        
        if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
            $sql .= " ORDER BY " . $data['sort'];
        } else {
            $sql .= " ORDER BY pd.name, pb.is_primary DESC";
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
     * الحصول على إجمالي عدد الباركودات
     */
    public function getTotalBarcodes($data = array()) {
        $sql = "
            SELECT COUNT(DISTINCT pb.barcode_id) AS total 
            FROM " . DB_PREFIX . "cod_product_barcode pb
            LEFT JOIN " . DB_PREFIX . "cod_product p ON (pb.product_id = p.product_id)
            LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id)
            WHERE pd.language_id = '" . (int)$this->config->get('config_language_id') . "'
        ";
        
        if (!empty($data['filter_barcode'])) {
            $sql .= " AND pb.barcode LIKE '%" . $this->db->escape($data['filter_barcode']) . "%'";
        }
        
        if (!empty($data['filter_product'])) {
            $sql .= " AND (pd.name LIKE '%" . $this->db->escape($data['filter_product']) . "%' OR p.model LIKE '%" . $this->db->escape($data['filter_product']) . "%')";
        }
        
        if (!empty($data['filter_type'])) {
            $sql .= " AND pb.barcode_type = '" . $this->db->escape($data['filter_type']) . "'";
        }
        
        if (isset($data['filter_status']) && $data['filter_status'] !== '') {
            $sql .= " AND pb.status = '" . (int)$data['filter_status'] . "'";
        }
        
        $query = $this->db->query($sql);
        
        return $query->row['total'];
    }
    
    /**
     * توليد باركود تلقائي
     */
    public function generateBarcode($product_id, $unit_id = 0, $option_id = 0, $option_value_id = 0, $type = 'EAN13') {
        // الحصول على معلومات المنتج
        $product_query = $this->db->query("
            SELECT * FROM " . DB_PREFIX . "cod_product 
            WHERE product_id = '" . (int)$product_id . "'
        ");
        
        if (!$product_query->num_rows) {
            return false;
        }
        
        $product = $product_query->row;
        
        switch ($type) {
            case 'EAN13':
                return $this->generateEAN13($product, $unit_id, $option_id, $option_value_id);
            case 'EAN8':
                return $this->generateEAN8($product, $unit_id, $option_id, $option_value_id);
            case 'UPC':
                return $this->generateUPC($product, $unit_id, $option_id, $option_value_id);
            case 'CODE128':
                return $this->generateCODE128($product, $unit_id, $option_id, $option_value_id);
            case 'CODE39':
                return $this->generateCODE39($product, $unit_id, $option_id, $option_value_id);
            case 'ISBN':
                return $this->generateISBN($product, $unit_id, $option_id, $option_value_id);
            default:
                return $this->generateEAN13($product, $unit_id, $option_id, $option_value_id);
        }
    }
    
    /**
     * توليد باركود EAN-13
     */
    private function generateEAN13($product, $unit_id, $option_id, $option_value_id) {
        // بناء الباركود من 12 رقم + رقم تحقق
        $barcode = '';
        
        // 3 أرقام للبلد (مصر = 622)
        $barcode .= '622';
        
        // 4 أرقام لمعرف الشركة
        $barcode .= str_pad($this->config->get('config_store_id'), 4, '0', STR_PAD_LEFT);
        
        // 5 أرقام للمنتج والوحدة والخيار
        $product_code = str_pad($product['product_id'], 3, '0', STR_PAD_LEFT);
        $unit_code = str_pad($unit_id, 1, '0', STR_PAD_LEFT);
        $option_code = str_pad($option_id, 1, '0', STR_PAD_LEFT);
        $barcode .= $product_code . $unit_code . $option_code;
        
        // حساب رقم التحقق
        $check_digit = $this->calculateEAN13CheckDigit($barcode);
        $barcode .= $check_digit;
        
        // التأكد من عدم تكرار الباركود
        while ($this->barcodeExists($barcode)) {
            $barcode = substr($barcode, 0, -2) . str_pad(rand(0, 99), 2, '0', STR_PAD_LEFT);
            $check_digit = $this->calculateEAN13CheckDigit(substr($barcode, 0, 12));
            $barcode = substr($barcode, 0, 12) . $check_digit;
        }
        
        return $barcode;
    }
    
    /**
     * توليد باركود EAN-8
     */
    private function generateEAN8($product, $unit_id, $option_id, $option_value_id) {
        // بناء الباركود من 7 أرقام + رقم تحقق
        $barcode = '';
        
        // 2 رقم للبلد
        $barcode .= '62';
        
        // 5 أرقام للمنتج والوحدة
        $product_code = str_pad($product['product_id'], 4, '0', STR_PAD_LEFT);
        $unit_code = str_pad($unit_id, 1, '0', STR_PAD_LEFT);
        $barcode .= $product_code . $unit_code;
        
        // حساب رقم التحقق
        $check_digit = $this->calculateEAN8CheckDigit($barcode);
        $barcode .= $check_digit;
        
        // التأكد من عدم تكرار الباركود
        while ($this->barcodeExists($barcode)) {
            $barcode = substr($barcode, 0, -2) . str_pad(rand(0, 99), 2, '0', STR_PAD_LEFT);
            $check_digit = $this->calculateEAN8CheckDigit(substr($barcode, 0, 7));
            $barcode = substr($barcode, 0, 7) . $check_digit;
        }
        
        return $barcode;
    }
    
    /**
     * توليد باركود UPC
     */
    private function generateUPC($product, $unit_id, $option_id, $option_value_id) {
        // مشابه لـ EAN-13 لكن بدون بادئة البلد
        $barcode = '';
        
        // 6 أرقام لمعرف الشركة
        $barcode .= str_pad($this->config->get('config_store_id'), 6, '0', STR_PAD_LEFT);
        
        // 5 أرقام للمنتج
        $product_code = str_pad($product['product_id'], 5, '0', STR_PAD_LEFT);
        $barcode .= $product_code;
        
        // حساب رقم التحقق
        $check_digit = $this->calculateUPCCheckDigit($barcode);
        $barcode .= $check_digit;
        
        return $barcode;
    }
    
    /**
     * توليد باركود CODE128
     */
    private function generateCODE128($product, $unit_id, $option_id, $option_value_id) {
        // CODE128 يمكن أن يحتوي على أحرف وأرقام
        $barcode = 'P' . str_pad($product['product_id'], 6, '0', STR_PAD_LEFT);
        
        if ($unit_id > 0) {
            $barcode .= 'U' . $unit_id;
        }
        
        if ($option_id > 0) {
            $barcode .= 'O' . $option_id;
        }
        
        return $barcode;
    }
    
    /**
     * توليد باركود CODE39
     */
    private function generateCODE39($product, $unit_id, $option_id, $option_value_id) {
        // CODE39 يدعم الأحرف الكبيرة والأرقام
        $barcode = 'PROD' . str_pad($product['product_id'], 6, '0', STR_PAD_LEFT);
        
        return $barcode;
    }
    
    /**
     * توليد باركود ISBN
     */
    private function generateISBN($product, $unit_id, $option_id, $option_value_id) {
        // ISBN-13 format
        $isbn = '978' . str_pad($product['product_id'], 9, '0', STR_PAD_LEFT);
        
        // حساب رقم التحقق
        $check_digit = $this->calculateISBNCheckDigit($isbn);
        $isbn .= $check_digit;
        
        return $isbn;
    }
    
    /**
     * حساب رقم التحقق لـ EAN-13
     */
    private function calculateEAN13CheckDigit($barcode) {
        $sum = 0;
        for ($i = 0; $i < 12; $i++) {
            $digit = (int)$barcode[$i];
            $sum += ($i % 2 == 0) ? $digit : $digit * 3;
        }
        return (10 - ($sum % 10)) % 10;
    }
    
    /**
     * حساب رقم التحقق لـ EAN-8
     */
    private function calculateEAN8CheckDigit($barcode) {
        $sum = 0;
        for ($i = 0; $i < 7; $i++) {
            $digit = (int)$barcode[$i];
            $sum += ($i % 2 == 0) ? $digit * 3 : $digit;
        }
        return (10 - ($sum % 10)) % 10;
    }
    
    /**
     * حساب رقم التحقق لـ UPC
     */
    private function calculateUPCCheckDigit($barcode) {
        $sum = 0;
        for ($i = 0; $i < 11; $i++) {
            $digit = (int)$barcode[$i];
            $sum += ($i % 2 == 0) ? $digit * 3 : $digit;
        }
        return (10 - ($sum % 10)) % 10;
    }
    
    /**
     * حساب رقم التحقق لـ ISBN
     */
    private function calculateISBNCheckDigit($isbn) {
        $sum = 0;
        for ($i = 0; $i < 12; $i++) {
            $digit = (int)$isbn[$i];
            $sum += ($i % 2 == 0) ? $digit : $digit * 3;
        }
        return (10 - ($sum % 10)) % 10;
    }
    
    /**
     * التحقق من وجود الباركود
     */
    public function barcodeExists($barcode, $exclude_id = 0) {
        $sql = "SELECT COUNT(*) as count FROM " . DB_PREFIX . "cod_product_barcode WHERE barcode = '" . $this->db->escape($barcode) . "'";
        
        if ($exclude_id > 0) {
            $sql .= " AND barcode_id != '" . (int)$exclude_id . "'";
        }
        
        $query = $this->db->query($sql);
        
        return $query->row['count'] > 0;
    }
    
    /**
     * الحصول على أنواع الباركود
     */
    public function getBarcodeTypes() {
        return $this->barcode_types;
    }
    
    /**
     * التحقق من صحة الباركود
     */
    public function validateBarcode($barcode, $type) {
        switch ($type) {
            case 'EAN13':
                return $this->validateEAN13($barcode);
            case 'EAN8':
                return $this->validateEAN8($barcode);
            case 'UPC':
                return $this->validateUPC($barcode);
            case 'CODE128':
                return $this->validateCODE128($barcode);
            case 'CODE39':
                return $this->validateCODE39($barcode);
            case 'ISBN':
                return $this->validateISBN($barcode);
            default:
                return false;
        }
    }
    
    /**
     * التحقق من صحة EAN-13
     */
    private function validateEAN13($barcode) {
        if (strlen($barcode) != 13 || !ctype_digit($barcode)) {
            return false;
        }
        
        $check_digit = $this->calculateEAN13CheckDigit(substr($barcode, 0, 12));
        return $check_digit == (int)$barcode[12];
    }
    
    /**
     * التحقق من صحة EAN-8
     */
    private function validateEAN8($barcode) {
        if (strlen($barcode) != 8 || !ctype_digit($barcode)) {
            return false;
        }
        
        $check_digit = $this->calculateEAN8CheckDigit(substr($barcode, 0, 7));
        return $check_digit == (int)$barcode[7];
    }
    
    /**
     * التحقق من صحة UPC
     */
    private function validateUPC($barcode) {
        if (strlen($barcode) != 12 || !ctype_digit($barcode)) {
            return false;
        }
        
        $check_digit = $this->calculateUPCCheckDigit(substr($barcode, 0, 11));
        return $check_digit == (int)$barcode[11];
    }
    
    /**
     * التحقق من صحة CODE128
     */
    private function validateCODE128($barcode) {
        // CODE128 يمكن أن يحتوي على أي أحرف ASCII
        return strlen($barcode) >= 1 && strlen($barcode) <= 80;
    }
    
    /**
     * التحقق من صحة CODE39
     */
    private function validateCODE39($barcode) {
        // CODE39 يدعم الأحرف الكبيرة والأرقام وبعض الرموز
        return preg_match('/^[A-Z0-9\-\.\$\/\+\%\s]+$/', $barcode);
    }
    
    /**
     * التحقق من صحة ISBN
     */
    private function validateISBN($barcode) {
        if (strlen($barcode) == 10) {
            // ISBN-10
            return $this->validateISBN10($barcode);
        } elseif (strlen($barcode) == 13) {
            // ISBN-13
            return $this->validateISBN13($barcode);
        }
        
        return false;
    }
    
    /**
     * التحقق من صحة ISBN-10
     */
    private function validateISBN10($isbn) {
        if (!preg_match('/^[0-9]{9}[0-9X]$/', $isbn)) {
            return false;
        }
        
        $sum = 0;
        for ($i = 0; $i < 9; $i++) {
            $sum += (int)$isbn[$i] * (10 - $i);
        }
        
        $check = $isbn[9];
        $check_digit = (11 - ($sum % 11)) % 11;
        
        return ($check_digit == 10 && $check == 'X') || ($check_digit == (int)$check);
    }
    
    /**
     * التحقق من صحة ISBN-13
     */
    private function validateISBN13($isbn) {
        if (strlen($isbn) != 13 || !ctype_digit($isbn)) {
            return false;
        }
        
        $check_digit = $this->calculateISBNCheckDigit(substr($isbn, 0, 12));
        return $check_digit == (int)$isbn[12];
    }
}
