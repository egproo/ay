<?php
/**
 * نموذج إدارة الوحدات المتطورة (Advanced Units Management Model)
 * 
 * الهدف: توفير نظام وحدات متطور مع تحويل تلقائي بين الوحدات
 * الميزات: وحدات أساسية وفرعية وعليا، معاملات تحويل، تحويل تلقائي
 * التكامل: مع المنتجات والتسعير والباركود والخيارات
 * 
 * @author ERP Team
 * @version 1.0
 * @since 2024
 */

class ModelInventoryUnits extends Model {
    
    /**
     * إضافة وحدة جديدة
     */
    public function addUnit($data) {
        $this->db->query("
            INSERT INTO " . DB_PREFIX . "cod_unit SET 
            name = '" . $this->db->escape($data['name']) . "',
            symbol = '" . $this->db->escape($data['symbol']) . "',
            type = '" . $this->db->escape($data['type']) . "',
            base_unit_id = '" . (int)$data['base_unit_id'] . "',
            conversion_factor = '" . (float)$data['conversion_factor'] . "',
            decimal_places = '" . (int)$data['decimal_places'] . "',
            status = '" . (int)$data['status'] . "',
            sort_order = '" . (int)$data['sort_order'] . "',
            date_added = NOW(),
            date_modified = NOW()
        ");
        
        $unit_id = $this->db->getLastId();
        
        // إدراج أوصاف الوحدة
        if (isset($data['unit_description'])) {
            foreach ($data['unit_description'] as $language_id => $value) {
                $this->db->query("
                    INSERT INTO " . DB_PREFIX . "cod_unit_description SET 
                    unit_id = '" . (int)$unit_id . "',
                    language_id = '" . (int)$language_id . "',
                    name = '" . $this->db->escape($value['name']) . "',
                    symbol = '" . $this->db->escape($value['symbol']) . "',
                    description = '" . $this->db->escape($value['description']) . "'
                ");
            }
        }
        
        return $unit_id;
    }
    
    /**
     * تعديل وحدة موجودة
     */
    public function editUnit($unit_id, $data) {
        $this->db->query("
            UPDATE " . DB_PREFIX . "cod_unit SET 
            name = '" . $this->db->escape($data['name']) . "',
            symbol = '" . $this->db->escape($data['symbol']) . "',
            type = '" . $this->db->escape($data['type']) . "',
            base_unit_id = '" . (int)$data['base_unit_id'] . "',
            conversion_factor = '" . (float)$data['conversion_factor'] . "',
            decimal_places = '" . (int)$data['decimal_places'] . "',
            status = '" . (int)$data['status'] . "',
            sort_order = '" . (int)$data['sort_order'] . "',
            date_modified = NOW()
            WHERE unit_id = '" . (int)$unit_id . "'
        ");
        
        // حذف وإعادة إدراج الأوصاف
        $this->db->query("DELETE FROM " . DB_PREFIX . "cod_unit_description WHERE unit_id = '" . (int)$unit_id . "'");
        
        if (isset($data['unit_description'])) {
            foreach ($data['unit_description'] as $language_id => $value) {
                $this->db->query("
                    INSERT INTO " . DB_PREFIX . "cod_unit_description SET 
                    unit_id = '" . (int)$unit_id . "',
                    language_id = '" . (int)$language_id . "',
                    name = '" . $this->db->escape($value['name']) . "',
                    symbol = '" . $this->db->escape($value['symbol']) . "',
                    description = '" . $this->db->escape($value['description']) . "'
                ");
            }
        }
    }
    
    /**
     * حذف وحدة
     */
    public function deleteUnit($unit_id) {
        $this->db->query("DELETE FROM " . DB_PREFIX . "cod_unit WHERE unit_id = '" . (int)$unit_id . "'");
        $this->db->query("DELETE FROM " . DB_PREFIX . "cod_unit_description WHERE unit_id = '" . (int)$unit_id . "'");
    }
    
    /**
     * الحصول على وحدة واحدة
     */
    public function getUnit($unit_id) {
        $query = $this->db->query("
            SELECT DISTINCT *
            FROM " . DB_PREFIX . "cod_unit u
            LEFT JOIN " . DB_PREFIX . "cod_unit_description ud ON (u.unit_id = ud.unit_id)
            WHERE u.unit_id = '" . (int)$unit_id . "' 
            AND ud.language_id = '" . (int)$this->config->get('config_language_id') . "'
        ");
        
        return $query->row;
    }
    
    /**
     * الحصول على قائمة الوحدات
     */
    public function getUnits($data = array()) {
        $sql = "
            SELECT u.unit_id, ud.name, u.symbol, u.type, u.conversion_factor, u.status, u.sort_order,
            (SELECT bu.name FROM " . DB_PREFIX . "cod_unit bu LEFT JOIN " . DB_PREFIX . "cod_unit_description bud ON (bu.unit_id = bud.unit_id) WHERE bu.unit_id = u.base_unit_id AND bud.language_id = '" . (int)$this->config->get('config_language_id') . "' LIMIT 1) as base_unit_name
            FROM " . DB_PREFIX . "cod_unit u
            LEFT JOIN " . DB_PREFIX . "cod_unit_description ud ON (u.unit_id = ud.unit_id)
            WHERE ud.language_id = '" . (int)$this->config->get('config_language_id') . "'
        ";
        
        if (!empty($data['filter_name'])) {
            $sql .= " AND ud.name LIKE '%" . $this->db->escape($data['filter_name']) . "%'";
        }
        
        if (isset($data['filter_status']) && $data['filter_status'] !== '') {
            $sql .= " AND u.status = '" . (int)$data['filter_status'] . "'";
        }
        
        if (!empty($data['filter_type'])) {
            $sql .= " AND u.type = '" . $this->db->escape($data['filter_type']) . "'";
        }
        
        $sort_data = array(
            'ud.name',
            'u.symbol',
            'u.type',
            'u.conversion_factor',
            'u.status',
            'u.sort_order'
        );
        
        if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
            $sql .= " ORDER BY " . $data['sort'];
        } else {
            $sql .= " ORDER BY u.sort_order, ud.name";
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
     * الحصول على إجمالي عدد الوحدات
     */
    public function getTotalUnits($data = array()) {
        $sql = "
            SELECT COUNT(DISTINCT u.unit_id) AS total 
            FROM " . DB_PREFIX . "cod_unit u
            LEFT JOIN " . DB_PREFIX . "cod_unit_description ud ON (u.unit_id = ud.unit_id)
            WHERE ud.language_id = '" . (int)$this->config->get('config_language_id') . "'
        ";
        
        if (!empty($data['filter_name'])) {
            $sql .= " AND ud.name LIKE '%" . $this->db->escape($data['filter_name']) . "%'";
        }
        
        if (isset($data['filter_status']) && $data['filter_status'] !== '') {
            $sql .= " AND u.status = '" . (int)$data['filter_status'] . "'";
        }
        
        if (!empty($data['filter_type'])) {
            $sql .= " AND u.type = '" . $this->db->escape($data['filter_type']) . "'";
        }
        
        $query = $this->db->query($sql);
        
        return $query->row['total'];
    }
    
    /**
     * الحصول على أوصاف الوحدة
     */
    public function getUnitDescriptions($unit_id) {
        $unit_description_data = array();
        
        $query = $this->db->query("
            SELECT * FROM " . DB_PREFIX . "cod_unit_description 
            WHERE unit_id = '" . (int)$unit_id . "'
        ");
        
        foreach ($query->rows as $result) {
            $unit_description_data[$result['language_id']] = array(
                'name'        => $result['name'],
                'symbol'      => $result['symbol'],
                'description' => $result['description']
            );
        }
        
        return $unit_description_data;
    }
    
    /**
     * تحويل الكمية بين الوحدات
     */
    public function convertQuantity($quantity, $from_unit_id, $to_unit_id) {
        if ($from_unit_id == $to_unit_id) {
            return $quantity;
        }
        
        // الحصول على معلومات الوحدة المصدر
        $from_unit = $this->getUnit($from_unit_id);
        $to_unit = $this->getUnit($to_unit_id);
        
        if (!$from_unit || !$to_unit) {
            return $quantity;
        }
        
        // تحويل إلى الوحدة الأساسية أولاً
        $base_quantity = $quantity;
        if ($from_unit['base_unit_id'] > 0) {
            $base_quantity = $quantity * $from_unit['conversion_factor'];
        }
        
        // تحويل من الوحدة الأساسية إلى الوحدة المطلوبة
        $converted_quantity = $base_quantity;
        if ($to_unit['base_unit_id'] > 0) {
            $converted_quantity = $base_quantity / $to_unit['conversion_factor'];
        }
        
        return round($converted_quantity, $to_unit['decimal_places']);
    }
    
    /**
     * الحصول على الوحدات الأساسية فقط
     */
    public function getBaseUnits() {
        $query = $this->db->query("
            SELECT u.unit_id, ud.name, u.symbol
            FROM " . DB_PREFIX . "cod_unit u
            LEFT JOIN " . DB_PREFIX . "cod_unit_description ud ON (u.unit_id = ud.unit_id)
            WHERE u.type = 'base' AND u.status = 1
            AND ud.language_id = '" . (int)$this->config->get('config_language_id') . "'
            ORDER BY u.sort_order, ud.name
        ");
        
        return $query->rows;
    }
    
    /**
     * الحصول على الوحدات الفرعية لوحدة أساسية
     */
    public function getSubUnits($base_unit_id) {
        $query = $this->db->query("
            SELECT u.unit_id, ud.name, u.symbol, u.conversion_factor
            FROM " . DB_PREFIX . "cod_unit u
            LEFT JOIN " . DB_PREFIX . "cod_unit_description ud ON (u.unit_id = ud.unit_id)
            WHERE u.base_unit_id = '" . (int)$base_unit_id . "' AND u.status = 1
            AND ud.language_id = '" . (int)$this->config->get('config_language_id') . "'
            ORDER BY u.conversion_factor DESC
        ");
        
        return $query->rows;
    }
    
    /**
     * الحصول على شجرة الوحدات (أساسية مع فرعياتها)
     */
    public function getUnitsTree() {
        $tree = array();
        
        // الحصول على الوحدات الأساسية
        $base_units = $this->getBaseUnits();
        
        foreach ($base_units as $base_unit) {
            $base_unit['children'] = $this->getSubUnits($base_unit['unit_id']);
            $tree[] = $base_unit;
        }
        
        return $tree;
    }
    
    /**
     * التحقق من إمكانية حذف الوحدة
     */
    public function canDeleteUnit($unit_id) {
        // التحقق من استخدام الوحدة في المنتجات
        $query = $this->db->query("
            SELECT COUNT(*) as count 
            FROM " . DB_PREFIX . "cod_product_unit 
            WHERE unit_id = '" . (int)$unit_id . "'
        ");
        
        if ($query->row['count'] > 0) {
            return false;
        }
        
        // التحقق من استخدام الوحدة في التسعير
        $query = $this->db->query("
            SELECT COUNT(*) as count 
            FROM " . DB_PREFIX . "cod_product_pricing 
            WHERE unit_id = '" . (int)$unit_id . "'
        ");
        
        if ($query->row['count'] > 0) {
            return false;
        }
        
        // التحقق من استخدام الوحدة في الباركود
        $query = $this->db->query("
            SELECT COUNT(*) as count 
            FROM " . DB_PREFIX . "cod_product_barcode 
            WHERE unit_id = '" . (int)$unit_id . "'
        ");
        
        if ($query->row['count'] > 0) {
            return false;
        }
        
        // التحقق من وجود وحدات فرعية
        $query = $this->db->query("
            SELECT COUNT(*) as count 
            FROM " . DB_PREFIX . "cod_unit 
            WHERE base_unit_id = '" . (int)$unit_id . "'
        ");
        
        if ($query->row['count'] > 0) {
            return false;
        }
        
        return true;
    }
    
    /**
     * البحث التلقائي في الوحدات
     */
    public function getUnitsAutocomplete($filter_name) {
        $query = $this->db->query("
            SELECT u.unit_id, ud.name, u.symbol
            FROM " . DB_PREFIX . "cod_unit u
            LEFT JOIN " . DB_PREFIX . "cod_unit_description ud ON (u.unit_id = ud.unit_id)
            WHERE ud.name LIKE '%" . $this->db->escape($filter_name) . "%' 
            AND u.status = 1
            AND ud.language_id = '" . (int)$this->config->get('config_language_id') . "'
            ORDER BY ud.name
            LIMIT 10
        ");
        
        return $query->rows;
    }
    
    /**
     * إنشاء وحدات افتراضية
     */
    public function createDefaultUnits() {
        $default_units = array(
            // وحدات الوزن
            array(
                'name' => 'كيلوجرام',
                'symbol' => 'كجم',
                'type' => 'base',
                'base_unit_id' => 0,
                'conversion_factor' => 1,
                'decimal_places' => 3
            ),
            array(
                'name' => 'جرام',
                'symbol' => 'جم',
                'type' => 'sub',
                'base_unit_id' => 1, // سيتم تحديثه
                'conversion_factor' => 0.001,
                'decimal_places' => 0
            ),
            // وحدات الحجم
            array(
                'name' => 'لتر',
                'symbol' => 'لتر',
                'type' => 'base',
                'base_unit_id' => 0,
                'conversion_factor' => 1,
                'decimal_places' => 3
            ),
            array(
                'name' => 'مليلتر',
                'symbol' => 'مل',
                'type' => 'sub',
                'base_unit_id' => 3, // سيتم تحديثه
                'conversion_factor' => 0.001,
                'decimal_places' => 0
            ),
            // وحدات العدد
            array(
                'name' => 'قطعة',
                'symbol' => 'قطعة',
                'type' => 'base',
                'base_unit_id' => 0,
                'conversion_factor' => 1,
                'decimal_places' => 0
            ),
            array(
                'name' => 'صندوق',
                'symbol' => 'صندوق',
                'type' => 'super',
                'base_unit_id' => 5, // سيتم تحديثه
                'conversion_factor' => 12,
                'decimal_places' => 0
            ),
            array(
                'name' => 'كرتونة',
                'symbol' => 'كرتونة',
                'type' => 'super',
                'base_unit_id' => 5, // سيتم تحديثه
                'conversion_factor' => 24,
                'decimal_places' => 0
            )
        );
        
        foreach ($default_units as $unit_data) {
            $unit_data['status'] = 1;
            $unit_data['sort_order'] = 0;
            
            $unit_data['unit_description'] = array(
                1 => array( // Arabic
                    'name' => $unit_data['name'],
                    'symbol' => $unit_data['symbol'],
                    'description' => 'وحدة افتراضية'
                ),
                2 => array( // English
                    'name' => $unit_data['name'],
                    'symbol' => $unit_data['symbol'],
                    'description' => 'Default unit'
                )
            );
            
            $this->addUnit($unit_data);
        }
    }
}
