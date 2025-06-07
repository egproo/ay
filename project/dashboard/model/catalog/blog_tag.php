<?php
class ModelCatalogBlogTag extends Model {
    public function addTag($data) {
        // توليد الـ slug إذا كان فارغاً
        if (empty($data['slug'])) {
            $data['slug'] = $this->generateSlug($data['name']);
        }

        $this->db->query("INSERT INTO " . DB_PREFIX . "blog_tag SET 
            name = '" . $this->db->escape($data['name']) . "', 
            slug = '" . $this->db->escape($data['slug']) . "', 
            date_added = NOW(), 
            date_modified = NOW()");

        return $this->db->getLastId();
    }

    public function editTag($tag_id, $data) {
        // توليد الـ slug إذا كان فارغاً
        if (empty($data['slug'])) {
            $data['slug'] = $this->generateSlug($data['name']);
        }

        $this->db->query("UPDATE " . DB_PREFIX . "blog_tag SET 
            name = '" . $this->db->escape($data['name']) . "', 
            slug = '" . $this->db->escape($data['slug']) . "', 
            date_modified = NOW() 
            WHERE tag_id = '" . (int)$tag_id . "'");
    }

    public function deleteTag($tag_id) {
        // حذف العلاقات مع المقالات
        $this->db->query("DELETE FROM " . DB_PREFIX . "blog_post_to_tag WHERE tag_id = '" . (int)$tag_id . "'");
        
        // حذف الوسم
        $this->db->query("DELETE FROM " . DB_PREFIX . "blog_tag WHERE tag_id = '" . (int)$tag_id . "'");
    }

    public function getTag($tag_id) {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "blog_tag WHERE tag_id = '" . (int)$tag_id . "'");

        return $query->row;
    }

    public function getBlogTags($data = array()) {
        $sql = "SELECT * FROM " . DB_PREFIX . "blog_tag";

        // تطبيق عوامل التصفية
        $where = array();

        if (!empty($data['filter_name'])) {
            $where[] = "name LIKE '%" . $this->db->escape($data['filter_name']) . "%'";
        }

        // إضافة شرط WHERE
        if ($where) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }

        // الترتيب
        if (isset($data['sort']) && $data['sort'] == 'posts_count') {
            // ترتيب خاص حسب عدد المقالات
            $sql = "SELECT t.*, COUNT(pt.post_id) as posts_count FROM " . DB_PREFIX . "blog_tag t 
                   LEFT JOIN " . DB_PREFIX . "blog_post_to_tag pt ON (t.tag_id = pt.tag_id)";
            
            if ($where) {
                $sql .= " WHERE " . implode(" AND ", $where);
            }
            
            $sql .= " GROUP BY t.tag_id";
            $sql .= " ORDER BY posts_count";
            
            if (isset($data['order']) && ($data['order'] == 'ASC')) {
                $sql .= " ASC";
            } else {
                $sql .= " DESC";
            }
        } else {
            if (isset($data['sort'])) {
                $sql .= " ORDER BY " . $data['sort'];
            } else {
                $sql .= " ORDER BY name";
            }

            if (isset($data['order']) && ($data['order'] == 'DESC')) {
                $sql .= " DESC";
            } else {
                $sql .= " ASC";
            }
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

    public function getTotalBlogTags($data = array()) {
        $sql = "SELECT COUNT(*) AS total FROM " . DB_PREFIX . "blog_tag";

        // تطبيق عوامل التصفية
        $where = array();

        if (!empty($data['filter_name'])) {
            $where[] = "name LIKE '%" . $this->db->escape($data['filter_name']) . "%'";
        }

        // إضافة شرط WHERE
        if ($where) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }

        $query = $this->db->query($sql);

        return $query->row['total'];
    }

    public function getTagPostsCount($tag_id) {
        $query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "blog_post_to_tag WHERE tag_id = '" . (int)$tag_id . "'");

        return $query->row['total'];
    }

    public function getTagPosts($tag_id) {
        $query = $this->db->query("SELECT p.post_id, p.title, p.date_published, p.status 
            FROM " . DB_PREFIX . "blog_post p 
            LEFT JOIN " . DB_PREFIX . "blog_post_to_tag pt ON (p.post_id = pt.post_id) 
            WHERE pt.tag_id = '" . (int)$tag_id . "' 
            ORDER BY p.date_published DESC 
            LIMIT 20");

        return $query->rows;
    }

    public function isSlugExists($slug, $tag_id = 0) {
        $query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "blog_tag WHERE slug = '" . $this->db->escape($slug) . "' AND tag_id != '" . (int)$tag_id . "'");
        
        return $query->row['total'] > 0;
    }

    public function getTagByName($name) {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "blog_tag WHERE LOWER(name) = LOWER('" . $this->db->escape($name) . "')");

        return $query->row;
    }

    public function addTagIfNotExists($name) {
        $tag_info = $this->getTagByName($name);
        
        if ($tag_info) {
            return $tag_info['tag_id'];
        } else {
            $data = array(
                'name' => $name,
                'slug' => $this->generateSlug($name)
            );
            
            return $this->addTag($data);
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