<?php
class ModelCatalogBlogCategory extends Model {
    public function addCategory($data) {
        // توليد الـ slug إذا كان فارغاً
        if (empty($data['slug'])) {
            $data['slug'] = $this->generateSlug($data['name']);
        }

        $this->db->query("INSERT INTO " . DB_PREFIX . "blog_category SET 
            parent_id = '" . (int)$data['parent_id'] . "', 
            name = '" . $this->db->escape($data['name']) . "', 
            slug = '" . $this->db->escape($data['slug']) . "', 
            description = '" . $this->db->escape($data['description']) . "', 
            image = '" . $this->db->escape($data['image']) . "', 
            meta_title = '" . $this->db->escape($data['meta_title']) . "', 
            meta_description = '" . $this->db->escape($data['meta_description']) . "', 
            meta_keywords = '" . $this->db->escape($data['meta_keywords']) . "', 
            status = '" . (int)$data['status'] . "', 
            sort_order = '" . (int)$data['sort_order'] . "', 
            date_added = NOW(), 
            date_modified = NOW()");

        return $this->db->getLastId();
    }

    public function editCategory($category_id, $data) {
        // توليد الـ slug إذا كان فارغاً
        if (empty($data['slug'])) {
            $data['slug'] = $this->generateSlug($data['name']);
        }

        $this->db->query("UPDATE " . DB_PREFIX . "blog_category SET 
            parent_id = '" . (int)$data['parent_id'] . "', 
            name = '" . $this->db->escape($data['name']) . "', 
            slug = '" . $this->db->escape($data['slug']) . "', 
            description = '" . $this->db->escape($data['description']) . "', 
            image = '" . $this->db->escape($data['image']) . "', 
            meta_title = '" . $this->db->escape($data['meta_title']) . "', 
            meta_description = '" . $this->db->escape($data['meta_description']) . "', 
            meta_keywords = '" . $this->db->escape($data['meta_keywords']) . "', 
            status = '" . (int)$data['status'] . "', 
            sort_order = '" . (int)$data['sort_order'] . "',  
            date_modified = NOW() 
            WHERE category_id = '" . (int)$category_id . "'");
    }

    public function deleteCategory($category_id) {
        // الحصول على التصنيفات الفرعية
        $query = $this->db->query("SELECT category_id FROM " . DB_PREFIX . "blog_category WHERE parent_id = '" . (int)$category_id . "'");
        
        foreach ($query->rows as $result) {
            $this->deleteCategory($result['category_id']);
        }
        
        // حذف العلاقات مع المقالات
        $this->db->query("DELETE FROM " . DB_PREFIX . "blog_post_to_category WHERE category_id = '" . (int)$category_id . "'");
        
        // حذف التصنيف
        $this->db->query("DELETE FROM " . DB_PREFIX . "blog_category WHERE category_id = '" . (int)$category_id . "'");
    }

    public function getCategory($category_id) {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "blog_category WHERE category_id = '" . (int)$category_id . "'");

        return $query->row;
    }

    public function getBlogCategories($data = array()) {
        $sql = "SELECT * FROM " . DB_PREFIX . "blog_category";

        // تطبيق عوامل التصفية
        $where = array();

        if (!empty($data['filter_name'])) {
            $where[] = "name LIKE '%" . $this->db->escape($data['filter_name']) . "%'";
        }

        if (isset($data['filter_status']) && $data['filter_status'] !== '') {
            $where[] = "status = '" . (int)$data['filter_status'] . "'";
        }

        if (isset($data['filter_parent_id']) && $data['filter_parent_id'] !== '') {
            $where[] = "parent_id = '" . (int)$data['filter_parent_id'] . "'";
        }

        // إضافة شرط WHERE
        if ($where) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }

        // الترتيب
        if (isset($data['sort'])) {
            $sql .= " ORDER BY " . $data['sort'];
        } else {
            $sql .= " ORDER BY sort_order, name";
        }

        if (isset($data['order']) && ($data['order'] == 'DESC')) {
            $sql .= " DESC";
        } else {
            $sql .= " ASC";
        }

        // التصفح
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

    public function getTotalBlogCategories($data = array()) {
        $sql = "SELECT COUNT(*) AS total FROM " . DB_PREFIX . "blog_category";

        // تطبيق عوامل التصفية
        $where = array();

        if (!empty($data['filter_name'])) {
            $where[] = "name LIKE '%" . $this->db->escape($data['filter_name']) . "%'";
        }

        if (isset($data['filter_status']) && $data['filter_status'] !== '') {
            $where[] = "status = '" . (int)$data['filter_status'] . "'";
        }

        if (isset($data['filter_parent_id']) && $data['filter_parent_id'] !== '') {
            $where[] = "parent_id = '" . (int)$data['filter_parent_id'] . "'";
        }

        // إضافة شرط WHERE
        if ($where) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }

        $query = $this->db->query($sql);

        return $query->row['total'];
    }

    public function getCategoryPostsCount($category_id) {
        $query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "blog_post_to_category WHERE category_id = '" . (int)$category_id . "'");

        return $query->row['total'];
    }

    public function getCategoryPosts($category_id) {
        $query = $this->db->query("SELECT p.post_id, p.title, p.date_published, p.status 
            FROM " . DB_PREFIX . "blog_post p 
            LEFT JOIN " . DB_PREFIX . "blog_post_to_category pc ON (p.post_id = pc.post_id) 
            WHERE pc.category_id = '" . (int)$category_id . "' 
            ORDER BY p.date_published DESC 
            LIMIT 20");

        return $query->rows;
    }

    public function isSlugExists($slug, $category_id = 0) {
        $query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "blog_category WHERE slug = '" . $this->db->escape($slug) . "' AND category_id != '" . (int)$category_id . "'");
        
        return $query->row['total'] > 0;
    }

    public function getCategoryPath($category_id) {
        $query = $this->db->query("SELECT parent_id FROM " . DB_PREFIX . "blog_category WHERE category_id = '" . (int)$category_id . "'");
        
        if ($query->row['parent_id'] == 0) {
            return $category_id;
        } else {
            return $this->getCategoryPath($query->row['parent_id']) . '_' . $category_id;
        }
    }

    public function getParentCategoriesPath($category_id) {
        $category_info = $this->getCategory($category_id);
        
        if ($category_info) {
            if ($category_info['parent_id'] == 0) {
                return array(array(
                    'category_id' => $category_info['category_id'],
                    'name' => $category_info['name']
                ));
            } else {
                $parent_categories = $this->getParentCategoriesPath($category_info['parent_id']);
                
                $parent_categories[] = array(
                    'category_id' => $category_info['category_id'],
                    'name' => $category_info['name']
                );
                
                return $parent_categories;
            }
        } else {
            return array();
        }
    }

    private function generateSlug($text) {
        // تحويل إلى حروف صغيرة
        $text = mb_strtolower($text, 'UTF-8');
        
        // استبدال المسافات بشرطات
        $text = preg_replace('/\s+/', '-', $text);
        
        // إزالة الأحرف الخاصة
        $text = preg_replace('/[^a-z0-9\-]/', '', $text);
        
        // إزالة الشرطات المتكررة
        $text = preg_replace('/-+/', '-', $text);
        
        // إزالة الشرطات من البداية والنهاية
        $text = trim($text, '-');
        
        return $text;
    }
}