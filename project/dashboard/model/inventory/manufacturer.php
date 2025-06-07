<?php
/**
 * نموذج إدارة العلامات التجارية المتطورة (Advanced Manufacturers Management Model)
 * 
 * الهدف: توفير إدارة شاملة للعلامات التجارية مع ميزات متقدمة
 * الميزات: معلومات مفصلة، ربط محاسبي، تقارير، تكويد تلقائي
 * التكامل: مع المنتجات والمحاسبة والتقارير والمشتريات
 * 
 * @author ERP Team
 * @version 1.0
 * @since 2024
 */

class ModelInventoryManufacturer extends Model {
    
    /**
     * إضافة علامة تجارية جديدة
     */
    public function addManufacturer($data) {
        $this->db->query("
            INSERT INTO " . DB_PREFIX . "manufacturer SET 
            name = '" . $this->db->escape($data['name']) . "',
            image = '" . $this->db->escape($data['image']) . "',
            sort_order = '" . (int)$data['sort_order'] . "',
            status = '" . (int)$data['status'] . "',
            code_prefix = '" . $this->db->escape($data['code_prefix']) . "',
            contact_person = '" . $this->db->escape($data['contact_person']) . "',
            email = '" . $this->db->escape($data['email']) . "',
            telephone = '" . $this->db->escape($data['telephone']) . "',
            fax = '" . $this->db->escape($data['fax']) . "',
            website = '" . $this->db->escape($data['website']) . "',
            address = '" . $this->db->escape($data['address']) . "',
            city = '" . $this->db->escape($data['city']) . "',
            country_id = '" . (int)$data['country_id'] . "',
            zone_id = '" . (int)$data['zone_id'] . "',
            tax_number = '" . $this->db->escape($data['tax_number']) . "',
            commercial_register = '" . $this->db->escape($data['commercial_register']) . "',
            account_id = '" . (int)$data['account_id'] . "',
            payment_terms = '" . (int)$data['payment_terms'] . "',
            credit_limit = '" . (float)$data['credit_limit'] . "',
            commission_rate = '" . (float)$data['commission_rate'] . "',
            notes = '" . $this->db->escape($data['notes']) . "',
            date_added = NOW(),
            date_modified = NOW()
        ");
        
        $manufacturer_id = $this->db->getLastId();
        
        // إدراج أوصاف العلامة التجارية
        if (isset($data['manufacturer_description'])) {
            foreach ($data['manufacturer_description'] as $language_id => $value) {
                $this->db->query("
                    INSERT INTO " . DB_PREFIX . "manufacturer_description SET 
                    manufacturer_id = '" . (int)$manufacturer_id . "',
                    language_id = '" . (int)$language_id . "',
                    name = '" . $this->db->escape($value['name']) . "',
                    description = '" . $this->db->escape($value['description']) . "',
                    meta_title = '" . $this->db->escape($value['meta_title']) . "',
                    meta_description = '" . $this->db->escape($value['meta_description']) . "',
                    meta_keyword = '" . $this->db->escape($value['meta_keyword']) . "'
                ");
            }
        }
        
        // إدراج المتاجر
        if (isset($data['manufacturer_store'])) {
            foreach ($data['manufacturer_store'] as $store_id) {
                $this->db->query("
                    INSERT INTO " . DB_PREFIX . "manufacturer_to_store SET 
                    manufacturer_id = '" . (int)$manufacturer_id . "',
                    store_id = '" . (int)$store_id . "'
                ");
            }
        }
        
        // إدراج كلمة SEO
        if (isset($data['keyword'])) {
            $this->db->query("
                INSERT INTO " . DB_PREFIX . "seo_url SET 
                store_id = '0',
                language_id = '" . (int)$this->config->get('config_language_id') . "',
                query = 'manufacturer_id=" . (int)$manufacturer_id . "',
                keyword = '" . $this->db->escape($data['keyword']) . "'
            ");
        }
        
        return $manufacturer_id;
    }
    
    /**
     * تعديل علامة تجارية موجودة
     */
    public function editManufacturer($manufacturer_id, $data) {
        $this->db->query("
            UPDATE " . DB_PREFIX . "manufacturer SET 
            name = '" . $this->db->escape($data['name']) . "',
            image = '" . $this->db->escape($data['image']) . "',
            sort_order = '" . (int)$data['sort_order'] . "',
            status = '" . (int)$data['status'] . "',
            code_prefix = '" . $this->db->escape($data['code_prefix']) . "',
            contact_person = '" . $this->db->escape($data['contact_person']) . "',
            email = '" . $this->db->escape($data['email']) . "',
            telephone = '" . $this->db->escape($data['telephone']) . "',
            fax = '" . $this->db->escape($data['fax']) . "',
            website = '" . $this->db->escape($data['website']) . "',
            address = '" . $this->db->escape($data['address']) . "',
            city = '" . $this->db->escape($data['city']) . "',
            country_id = '" . (int)$data['country_id'] . "',
            zone_id = '" . (int)$data['zone_id'] . "',
            tax_number = '" . $this->db->escape($data['tax_number']) . "',
            commercial_register = '" . $this->db->escape($data['commercial_register']) . "',
            account_id = '" . (int)$data['account_id'] . "',
            payment_terms = '" . (int)$data['payment_terms'] . "',
            credit_limit = '" . (float)$data['credit_limit'] . "',
            commission_rate = '" . (float)$data['commission_rate'] . "',
            notes = '" . $this->db->escape($data['notes']) . "',
            date_modified = NOW()
            WHERE manufacturer_id = '" . (int)$manufacturer_id . "'
        ");
        
        // حذف وإعادة إدراج الأوصاف
        $this->db->query("DELETE FROM " . DB_PREFIX . "manufacturer_description WHERE manufacturer_id = '" . (int)$manufacturer_id . "'");
        
        if (isset($data['manufacturer_description'])) {
            foreach ($data['manufacturer_description'] as $language_id => $value) {
                $this->db->query("
                    INSERT INTO " . DB_PREFIX . "manufacturer_description SET 
                    manufacturer_id = '" . (int)$manufacturer_id . "',
                    language_id = '" . (int)$language_id . "',
                    name = '" . $this->db->escape($value['name']) . "',
                    description = '" . $this->db->escape($value['description']) . "',
                    meta_title = '" . $this->db->escape($value['meta_title']) . "',
                    meta_description = '" . $this->db->escape($value['meta_description']) . "',
                    meta_keyword = '" . $this->db->escape($value['meta_keyword']) . "'
                ");
            }
        }
        
        // تحديث المتاجر
        $this->db->query("DELETE FROM " . DB_PREFIX . "manufacturer_to_store WHERE manufacturer_id = '" . (int)$manufacturer_id . "'");
        
        if (isset($data['manufacturer_store'])) {
            foreach ($data['manufacturer_store'] as $store_id) {
                $this->db->query("
                    INSERT INTO " . DB_PREFIX . "manufacturer_to_store SET 
                    manufacturer_id = '" . (int)$manufacturer_id . "',
                    store_id = '" . (int)$store_id . "'
                ");
            }
        }
        
        // تحديث كلمة SEO
        $this->db->query("DELETE FROM " . DB_PREFIX . "seo_url WHERE query = 'manufacturer_id=" . (int)$manufacturer_id . "'");
        
        if (isset($data['keyword'])) {
            $this->db->query("
                INSERT INTO " . DB_PREFIX . "seo_url SET 
                store_id = '0',
                language_id = '" . (int)$this->config->get('config_language_id') . "',
                query = 'manufacturer_id=" . (int)$manufacturer_id . "',
                keyword = '" . $this->db->escape($data['keyword']) . "'
            ");
        }
    }
    
    /**
     * حذف علامة تجارية
     */
    public function deleteManufacturer($manufacturer_id) {
        $this->db->query("DELETE FROM " . DB_PREFIX . "manufacturer WHERE manufacturer_id = '" . (int)$manufacturer_id . "'");
        $this->db->query("DELETE FROM " . DB_PREFIX . "manufacturer_description WHERE manufacturer_id = '" . (int)$manufacturer_id . "'");
        $this->db->query("DELETE FROM " . DB_PREFIX . "manufacturer_to_store WHERE manufacturer_id = '" . (int)$manufacturer_id . "'");
        $this->db->query("DELETE FROM " . DB_PREFIX . "seo_url WHERE query = 'manufacturer_id=" . (int)$manufacturer_id . "'");
    }
    
    /**
     * الحصول على علامة تجارية واحدة
     */
    public function getManufacturer($manufacturer_id) {
        $query = $this->db->query("
            SELECT DISTINCT *, 
            (SELECT keyword FROM " . DB_PREFIX . "seo_url WHERE query = 'manufacturer_id=" . (int)$manufacturer_id . "' AND store_id = '0' AND language_id = '" . (int)$this->config->get('config_language_id') . "' LIMIT 1) AS keyword
            FROM " . DB_PREFIX . "manufacturer m
            LEFT JOIN " . DB_PREFIX . "manufacturer_description md ON (m.manufacturer_id = md.manufacturer_id)
            WHERE m.manufacturer_id = '" . (int)$manufacturer_id . "' 
            AND md.language_id = '" . (int)$this->config->get('config_language_id') . "'
        ");
        
        return $query->row;
    }
    
    /**
     * الحصول على قائمة العلامات التجارية
     */
    public function getManufacturers($data = array()) {
        $sql = "
            SELECT m.manufacturer_id, md.name, m.image, m.sort_order, m.status, m.code_prefix,
            m.contact_person, m.email, m.telephone, m.website, m.date_added
            FROM " . DB_PREFIX . "manufacturer m
            LEFT JOIN " . DB_PREFIX . "manufacturer_description md ON (m.manufacturer_id = md.manufacturer_id)
            WHERE md.language_id = '" . (int)$this->config->get('config_language_id') . "'
        ";
        
        if (!empty($data['filter_name'])) {
            $sql .= " AND md.name LIKE '%" . $this->db->escape($data['filter_name']) . "%'";
        }
        
        if (isset($data['filter_status']) && $data['filter_status'] !== '') {
            $sql .= " AND m.status = '" . (int)$data['filter_status'] . "'";
        }
        
        $sort_data = array(
            'md.name',
            'm.sort_order',
            'm.status',
            'm.date_added'
        );
        
        if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
            $sql .= " ORDER BY " . $data['sort'];
        } else {
            $sql .= " ORDER BY md.name";
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
     * الحصول على إجمالي عدد العلامات التجارية
     */
    public function getTotalManufacturers($data = array()) {
        $sql = "
            SELECT COUNT(DISTINCT m.manufacturer_id) AS total 
            FROM " . DB_PREFIX . "manufacturer m
            LEFT JOIN " . DB_PREFIX . "manufacturer_description md ON (m.manufacturer_id = md.manufacturer_id)
            WHERE md.language_id = '" . (int)$this->config->get('config_language_id') . "'
        ";
        
        if (!empty($data['filter_name'])) {
            $sql .= " AND md.name LIKE '%" . $this->db->escape($data['filter_name']) . "%'";
        }
        
        if (isset($data['filter_status']) && $data['filter_status'] !== '') {
            $sql .= " AND m.status = '" . (int)$data['filter_status'] . "'";
        }
        
        $query = $this->db->query($sql);
        
        return $query->row['total'];
    }
    
    /**
     * الحصول على أوصاف العلامة التجارية
     */
    public function getManufacturerDescriptions($manufacturer_id) {
        $manufacturer_description_data = array();
        
        $query = $this->db->query("
            SELECT * FROM " . DB_PREFIX . "manufacturer_description 
            WHERE manufacturer_id = '" . (int)$manufacturer_id . "'
        ");
        
        foreach ($query->rows as $result) {
            $manufacturer_description_data[$result['language_id']] = array(
                'name'             => $result['name'],
                'description'      => $result['description'],
                'meta_title'       => $result['meta_title'],
                'meta_description' => $result['meta_description'],
                'meta_keyword'     => $result['meta_keyword']
            );
        }
        
        return $manufacturer_description_data;
    }
    
    /**
     * الحصول على متاجر العلامة التجارية
     */
    public function getManufacturerStores($manufacturer_id) {
        $manufacturer_store_data = array();
        
        $query = $this->db->query("
            SELECT * FROM " . DB_PREFIX . "manufacturer_to_store 
            WHERE manufacturer_id = '" . (int)$manufacturer_id . "'
        ");
        
        foreach ($query->rows as $result) {
            $manufacturer_store_data[] = $result['store_id'];
        }
        
        return $manufacturer_store_data;
    }
    
    /**
     * البحث التلقائي في العلامات التجارية
     */
    public function getManufacturersAutocomplete($filter_name) {
        $query = $this->db->query("
            SELECT m.manufacturer_id, md.name
            FROM " . DB_PREFIX . "manufacturer m
            LEFT JOIN " . DB_PREFIX . "manufacturer_description md ON (m.manufacturer_id = md.manufacturer_id)
            WHERE md.name LIKE '%" . $this->db->escape($filter_name) . "%' 
            AND m.status = 1
            AND md.language_id = '" . (int)$this->config->get('config_language_id') . "'
            ORDER BY md.name
            LIMIT 10
        ");
        
        return $query->rows;
    }
    
    /**
     * التحقق من إمكانية حذف العلامة التجارية
     */
    public function canDeleteManufacturer($manufacturer_id) {
        // التحقق من وجود منتجات للعلامة التجارية
        $query = $this->db->query("
            SELECT COUNT(*) as count 
            FROM " . DB_PREFIX . "cod_product 
            WHERE manufacturer_id = '" . (int)$manufacturer_id . "'
        ");
        
        return $query->row['count'] == 0;
    }
    
    /**
     * توليد بادئة كود العلامة التجارية
     */
    public function generateManufacturerPrefix($name) {
        // استخراج الأحرف الأولى من الاسم
        $words = explode(' ', $name);
        $prefix = '';
        
        foreach ($words as $word) {
            if (strlen($word) > 0) {
                $prefix .= strtoupper(substr($word, 0, 1));
            }
        }
        
        // التأكد من عدم تكرار البادئة
        $counter = 1;
        $original_prefix = $prefix;
        
        while ($this->isPrefixExists($prefix)) {
            $prefix = $original_prefix . $counter;
            $counter++;
        }
        
        return $prefix;
    }
    
    /**
     * التحقق من وجود بادئة الكود
     */
    private function isPrefixExists($prefix) {
        $query = $this->db->query("
            SELECT COUNT(*) as count 
            FROM " . DB_PREFIX . "manufacturer 
            WHERE code_prefix = '" . $this->db->escape($prefix) . "'
        ");
        
        return $query->row['count'] > 0;
    }
    
    /**
     * الحصول على إحصائيات العلامة التجارية
     */
    public function getManufacturerStats($manufacturer_id) {
        $stats = array();
        
        // عدد المنتجات
        $query = $this->db->query("
            SELECT COUNT(*) as count 
            FROM " . DB_PREFIX . "cod_product 
            WHERE manufacturer_id = '" . (int)$manufacturer_id . "' AND status = 1
        ");
        $stats['total_products'] = $query->row['count'];
        
        // إجمالي قيمة المخزون
        $query = $this->db->query("
            SELECT SUM(pi.quantity * p.average_cost) as total_value
            FROM " . DB_PREFIX . "cod_product p
            LEFT JOIN " . DB_PREFIX . "cod_product_inventory pi ON (p.product_id = pi.product_id)
            WHERE p.manufacturer_id = '" . (int)$manufacturer_id . "' AND p.status = 1
        ");
        $stats['inventory_value'] = $query->row['total_value'] ? (float)$query->row['total_value'] : 0;
        
        // إجمالي المبيعات (آخر 12 شهر)
        $query = $this->db->query("
            SELECT SUM(pm.quantity * pm.unit_cost) as total_sales
            FROM " . DB_PREFIX . "cod_product p
            LEFT JOIN " . DB_PREFIX . "cod_product_movement pm ON (p.product_id = pm.product_id)
            WHERE p.manufacturer_id = '" . (int)$manufacturer_id . "' 
            AND pm.movement_type = 'sale'
            AND pm.date_added >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
        ");
        $stats['annual_sales'] = $query->row['total_sales'] ? (float)$query->row['total_sales'] : 0;
        
        return $stats;
    }
    
    /**
     * الحصول على أفضل المنتجات للعلامة التجارية
     */
    public function getTopProducts($manufacturer_id, $limit = 10) {
        $query = $this->db->query("
            SELECT p.product_id, pd.name, 
            SUM(pm.quantity) as total_sold,
            SUM(pm.quantity * pm.unit_cost) as total_value
            FROM " . DB_PREFIX . "cod_product p
            LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id)
            LEFT JOIN " . DB_PREFIX . "cod_product_movement pm ON (p.product_id = pm.product_id)
            WHERE p.manufacturer_id = '" . (int)$manufacturer_id . "'
            AND pm.movement_type = 'sale'
            AND pm.date_added >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
            AND pd.language_id = '" . (int)$this->config->get('config_language_id') . "'
            GROUP BY p.product_id
            ORDER BY total_sold DESC
            LIMIT " . (int)$limit
        );
        
        return $query->rows;
    }
}
