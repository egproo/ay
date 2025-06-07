<?php
/**
 * نموذج إدارة التصنيفات المتطورة (Advanced Categories Management Model)
 * 
 * الهدف: توفير نظام تصنيفات هرمي متطور مع ميزات متقدمة
 * الميزات: هيكل شجري، تكويد تلقائي، صور، SEO، ربط بالمحاسبة
 * التكامل: مع المنتجات والمحاسبة والتقارير
 * 
 * @author ERP Team
 * @version 1.0
 * @since 2024
 */

class ModelInventoryCategory extends Model {
    
    /**
     * إضافة تصنيف جديد
     */
    public function addCategory($data) {
        $this->db->query("
            INSERT INTO " . DB_PREFIX . "category SET 
            image = '" . $this->db->escape($data['image']) . "',
            parent_id = '" . (int)$data['parent_id'] . "',
            `top` = '" . (int)$data['top'] . "',
            `column` = '" . (int)$data['column'] . "',
            sort_order = '" . (int)$data['sort_order'] . "',
            status = '" . (int)$data['status'] . "',
            code_prefix = '" . $this->db->escape($data['code_prefix']) . "',
            account_id = '" . (int)$data['account_id'] . "',
            commission_rate = '" . (float)$data['commission_rate'] . "',
            date_added = NOW(),
            date_modified = NOW()
        ");
        
        $category_id = $this->db->getLastId();
        
        // إدراج أوصاف التصنيف
        if (isset($data['category_description'])) {
            foreach ($data['category_description'] as $language_id => $value) {
                $this->db->query("
                    INSERT INTO " . DB_PREFIX . "category_description SET 
                    category_id = '" . (int)$category_id . "',
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
        if (isset($data['category_store'])) {
            foreach ($data['category_store'] as $store_id) {
                $this->db->query("
                    INSERT INTO " . DB_PREFIX . "category_to_store SET 
                    category_id = '" . (int)$category_id . "',
                    store_id = '" . (int)$store_id . "'
                ");
            }
        }
        
        // إدراج التخطيطات
        if (isset($data['category_layout'])) {
            foreach ($data['category_layout'] as $store_id => $layout_id) {
                $this->db->query("
                    INSERT INTO " . DB_PREFIX . "category_to_layout SET 
                    category_id = '" . (int)$category_id . "',
                    store_id = '" . (int)$store_id . "',
                    layout_id = '" . (int)$layout_id . "'
                ");
            }
        }
        
        // إدراج كلمة SEO
        if (isset($data['keyword'])) {
            $this->db->query("
                INSERT INTO " . DB_PREFIX . "seo_url SET 
                store_id = '0',
                language_id = '" . (int)$this->config->get('config_language_id') . "',
                query = 'category_id=" . (int)$category_id . "',
                keyword = '" . $this->db->escape($data['keyword']) . "'
            ");
        }
        
        // تحديث مسار التصنيف
        $this->updateCategoryPath($category_id);
        
        return $category_id;
    }
    
    /**
     * تعديل تصنيف موجود
     */
    public function editCategory($category_id, $data) {
        $this->db->query("
            UPDATE " . DB_PREFIX . "category SET 
            image = '" . $this->db->escape($data['image']) . "',
            parent_id = '" . (int)$data['parent_id'] . "',
            `top` = '" . (int)$data['top'] . "',
            `column` = '" . (int)$data['column'] . "',
            sort_order = '" . (int)$data['sort_order'] . "',
            status = '" . (int)$data['status'] . "',
            code_prefix = '" . $this->db->escape($data['code_prefix']) . "',
            account_id = '" . (int)$data['account_id'] . "',
            commission_rate = '" . (float)$data['commission_rate'] . "',
            date_modified = NOW()
            WHERE category_id = '" . (int)$category_id . "'
        ");
        
        // حذف وإعادة إدراج الأوصاف
        $this->db->query("DELETE FROM " . DB_PREFIX . "category_description WHERE category_id = '" . (int)$category_id . "'");
        
        if (isset($data['category_description'])) {
            foreach ($data['category_description'] as $language_id => $value) {
                $this->db->query("
                    INSERT INTO " . DB_PREFIX . "category_description SET 
                    category_id = '" . (int)$category_id . "',
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
        $this->db->query("DELETE FROM " . DB_PREFIX . "category_to_store WHERE category_id = '" . (int)$category_id . "'");
        
        if (isset($data['category_store'])) {
            foreach ($data['category_store'] as $store_id) {
                $this->db->query("
                    INSERT INTO " . DB_PREFIX . "category_to_store SET 
                    category_id = '" . (int)$category_id . "',
                    store_id = '" . (int)$store_id . "'
                ");
            }
        }
        
        // تحديث التخطيطات
        $this->db->query("DELETE FROM " . DB_PREFIX . "category_to_layout WHERE category_id = '" . (int)$category_id . "'");
        
        if (isset($data['category_layout'])) {
            foreach ($data['category_layout'] as $store_id => $layout_id) {
                $this->db->query("
                    INSERT INTO " . DB_PREFIX . "category_to_layout SET 
                    category_id = '" . (int)$category_id . "',
                    store_id = '" . (int)$store_id . "',
                    layout_id = '" . (int)$layout_id . "'
                ");
            }
        }
        
        // تحديث كلمة SEO
        $this->db->query("DELETE FROM " . DB_PREFIX . "seo_url WHERE query = 'category_id=" . (int)$category_id . "'");
        
        if (isset($data['keyword'])) {
            $this->db->query("
                INSERT INTO " . DB_PREFIX . "seo_url SET 
                store_id = '0',
                language_id = '" . (int)$this->config->get('config_language_id') . "',
                query = 'category_id=" . (int)$category_id . "',
                keyword = '" . $this->db->escape($data['keyword']) . "'
            ");
        }
        
        // تحديث مسار التصنيف
        $this->updateCategoryPath($category_id);
    }
    
    /**
     * حذف تصنيف
     */
    public function deleteCategory($category_id) {
        // حذف جميع البيانات المرتبطة
        $this->db->query("DELETE FROM " . DB_PREFIX . "category WHERE category_id = '" . (int)$category_id . "'");
        $this->db->query("DELETE FROM " . DB_PREFIX . "category_description WHERE category_id = '" . (int)$category_id . "'");
        $this->db->query("DELETE FROM " . DB_PREFIX . "category_to_store WHERE category_id = '" . (int)$category_id . "'");
        $this->db->query("DELETE FROM " . DB_PREFIX . "category_to_layout WHERE category_id = '" . (int)$category_id . "'");
        $this->db->query("DELETE FROM " . DB_PREFIX . "category_path WHERE category_id = '" . (int)$category_id . "' OR path_id = '" . (int)$category_id . "'");
        $this->db->query("DELETE FROM " . DB_PREFIX . "category_filter WHERE category_id = '" . (int)$category_id . "'");
        $this->db->query("DELETE FROM " . DB_PREFIX . "seo_url WHERE query = 'category_id=" . (int)$category_id . "'");
        
        // حذف ربط المنتجات
        $this->db->query("DELETE FROM " . DB_PREFIX . "product_to_category WHERE category_id = '" . (int)$category_id . "'");
        
        // تحديث التصنيفات الفرعية لتصبح بدون أب
        $this->db->query("UPDATE " . DB_PREFIX . "category SET parent_id = '0' WHERE parent_id = '" . (int)$category_id . "'");
    }
    
    /**
     * الحصول على تصنيف واحد
     */
    public function getCategory($category_id) {
        $query = $this->db->query("
            SELECT DISTINCT *, 
            (SELECT keyword FROM " . DB_PREFIX . "seo_url WHERE query = 'category_id=" . (int)$category_id . "' AND store_id = '0' AND language_id = '" . (int)$this->config->get('config_language_id') . "' LIMIT 1) AS keyword
            FROM " . DB_PREFIX . "category c
            LEFT JOIN " . DB_PREFIX . "category_description cd ON (c.category_id = cd.category_id)
            WHERE c.category_id = '" . (int)$category_id . "' 
            AND cd.language_id = '" . (int)$this->config->get('config_language_id') . "'
        ");
        
        return $query->row;
    }
    
    /**
     * الحصول على قائمة التصنيفات
     */
    public function getCategories($data = array()) {
        $sql = "
            SELECT cp.category_id AS category_id, 
            GROUP_CONCAT(cd1.name ORDER BY cp.level SEPARATOR '&nbsp;&nbsp;&gt;&nbsp;&nbsp;') AS name, 
            c1.parent_id, c1.sort_order, c1.status, c1.code_prefix
            FROM " . DB_PREFIX . "category_path cp
            LEFT JOIN " . DB_PREFIX . "category c1 ON (cp.category_id = c1.category_id)
            LEFT JOIN " . DB_PREFIX . "category c2 ON (cp.path_id = c2.category_id)
            LEFT JOIN " . DB_PREFIX . "category_description cd1 ON (cp.path_id = cd1.category_id)
            LEFT JOIN " . DB_PREFIX . "category_description cd2 ON (cp.category_id = cd2.category_id)
            WHERE cd1.language_id = '" . (int)$this->config->get('config_language_id') . "' 
            AND cd2.language_id = '" . (int)$this->config->get('config_language_id') . "'
        ";
        
        if (!empty($data['filter_name'])) {
            $sql .= " AND cd2.name LIKE '%" . $this->db->escape($data['filter_name']) . "%'";
        }
        
        if (isset($data['filter_status']) && $data['filter_status'] !== '') {
            $sql .= " AND c1.status = '" . (int)$data['filter_status'] . "'";
        }
        
        $sql .= " GROUP BY cp.category_id";
        
        $sort_data = array(
            'name',
            'sort_order',
            'status'
        );
        
        if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
            if ($data['sort'] == 'name') {
                $sql .= " ORDER BY cd2.name";
            } else {
                $sql .= " ORDER BY " . $data['sort'];
            }
        } else {
            $sql .= " ORDER BY sort_order, name";
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
     * الحصول على إجمالي عدد التصنيفات
     */
    public function getTotalCategories($data = array()) {
        $sql = "
            SELECT COUNT(DISTINCT c.category_id) AS total 
            FROM " . DB_PREFIX . "category c
            LEFT JOIN " . DB_PREFIX . "category_description cd ON (c.category_id = cd.category_id)
            WHERE cd.language_id = '" . (int)$this->config->get('config_language_id') . "'
        ";
        
        if (!empty($data['filter_name'])) {
            $sql .= " AND cd.name LIKE '%" . $this->db->escape($data['filter_name']) . "%'";
        }
        
        if (isset($data['filter_status']) && $data['filter_status'] !== '') {
            $sql .= " AND c.status = '" . (int)$data['filter_status'] . "'";
        }
        
        $query = $this->db->query($sql);
        
        return $query->row['total'];
    }
    
    /**
     * الحصول على أوصاف التصنيف
     */
    public function getCategoryDescriptions($category_id) {
        $category_description_data = array();
        
        $query = $this->db->query("
            SELECT * FROM " . DB_PREFIX . "category_description 
            WHERE category_id = '" . (int)$category_id . "'
        ");
        
        foreach ($query->rows as $result) {
            $category_description_data[$result['language_id']] = array(
                'name'             => $result['name'],
                'description'      => $result['description'],
                'meta_title'       => $result['meta_title'],
                'meta_description' => $result['meta_description'],
                'meta_keyword'     => $result['meta_keyword']
            );
        }
        
        return $category_description_data;
    }
    
    /**
     * الحصول على متاجر التصنيف
     */
    public function getCategoryStores($category_id) {
        $category_store_data = array();
        
        $query = $this->db->query("
            SELECT * FROM " . DB_PREFIX . "category_to_store 
            WHERE category_id = '" . (int)$category_id . "'
        ");
        
        foreach ($query->rows as $result) {
            $category_store_data[] = $result['store_id'];
        }
        
        return $category_store_data;
    }
    
    /**
     * الحصول على تخطيطات التصنيف
     */
    public function getCategoryLayouts($category_id) {
        $category_layout_data = array();
        
        $query = $this->db->query("
            SELECT * FROM " . DB_PREFIX . "category_to_layout 
            WHERE category_id = '" . (int)$category_id . "'
        ");
        
        foreach ($query->rows as $result) {
            $category_layout_data[$result['store_id']] = $result['layout_id'];
        }
        
        return $category_layout_data;
    }
    
    /**
     * تحديث مسار التصنيف
     */
    public function updateCategoryPath($category_id) {
        // حذف المسارات الحالية
        $this->db->query("DELETE FROM " . DB_PREFIX . "category_path WHERE category_id = '" . (int)$category_id . "'");
        
        // الحصول على معلومات التصنيف
        $query = $this->db->query("SELECT parent_id FROM " . DB_PREFIX . "category WHERE category_id = '" . (int)$category_id . "'");
        
        if ($query->num_rows) {
            $level = 0;
            
            // إضافة المسار للتصنيف نفسه
            $this->db->query("
                INSERT INTO " . DB_PREFIX . "category_path SET 
                category_id = '" . (int)$category_id . "',
                path_id = '" . (int)$category_id . "',
                level = '" . (int)$level . "'
            ");
            
            $parent_id = $query->row['parent_id'];
            
            // إضافة مسارات الآباء
            while ($parent_id) {
                $level++;
                
                $this->db->query("
                    INSERT INTO " . DB_PREFIX . "category_path SET 
                    category_id = '" . (int)$category_id . "',
                    path_id = '" . (int)$parent_id . "',
                    level = '" . (int)$level . "'
                ");
                
                $query = $this->db->query("SELECT parent_id FROM " . DB_PREFIX . "category WHERE category_id = '" . (int)$parent_id . "'");
                
                if ($query->num_rows) {
                    $parent_id = $query->row['parent_id'];
                } else {
                    break;
                }
            }
        }
    }
    
    /**
     * الحصول على شجرة التصنيفات
     */
    public function getCategoriesTree($parent_id = 0) {
        $query = $this->db->query("
            SELECT c.category_id, cd.name, c.parent_id, c.sort_order, c.status, c.code_prefix
            FROM " . DB_PREFIX . "category c
            LEFT JOIN " . DB_PREFIX . "category_description cd ON (c.category_id = cd.category_id)
            WHERE c.parent_id = '" . (int)$parent_id . "' 
            AND cd.language_id = '" . (int)$this->config->get('config_language_id') . "'
            AND c.status = 1
            ORDER BY c.sort_order, cd.name
        ");
        
        $categories = array();
        
        foreach ($query->rows as $category) {
            $category['children'] = $this->getCategoriesTree($category['category_id']);
            $categories[] = $category;
        }
        
        return $categories;
    }
    
    /**
     * البحث التلقائي في التصنيفات
     */
    public function getCategoriesAutocomplete($filter_name) {
        $query = $this->db->query("
            SELECT c.category_id, cd.name
            FROM " . DB_PREFIX . "category c
            LEFT JOIN " . DB_PREFIX . "category_description cd ON (c.category_id = cd.category_id)
            WHERE cd.name LIKE '%" . $this->db->escape($filter_name) . "%' 
            AND c.status = 1
            AND cd.language_id = '" . (int)$this->config->get('config_language_id') . "'
            ORDER BY cd.name
            LIMIT 10
        ");
        
        return $query->rows;
    }
    
    /**
     * التحقق من إمكانية حذف التصنيف
     */
    public function canDeleteCategory($category_id) {
        // التحقق من وجود منتجات في التصنيف
        $query = $this->db->query("
            SELECT COUNT(*) as count 
            FROM " . DB_PREFIX . "product_to_category 
            WHERE category_id = '" . (int)$category_id . "'
        ");
        
        if ($query->row['count'] > 0) {
            return false;
        }
        
        // التحقق من وجود تصنيفات فرعية
        $query = $this->db->query("
            SELECT COUNT(*) as count 
            FROM " . DB_PREFIX . "category 
            WHERE parent_id = '" . (int)$category_id . "'
        ");
        
        if ($query->row['count'] > 0) {
            return false;
        }
        
        return true;
    }
    
    /**
     * توليد بادئة كود التصنيف
     */
    public function generateCategoryPrefix($name) {
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
            FROM " . DB_PREFIX . "category 
            WHERE code_prefix = '" . $this->db->escape($prefix) . "'
        ");
        
        return $query->row['count'] > 0;
    }
}
