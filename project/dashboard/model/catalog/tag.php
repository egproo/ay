<?php
class ModelCatalogBlogTag extends Model {
    public function addTag($data) {
        // Generación del slug si está vacío
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
        // Generación del slug si está vacío
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
        // Eliminar relaciones con posts
        $this->db->query("DELETE FROM " . DB_PREFIX . "blog_post_to_tag WHERE tag_id = '" . (int)$tag_id . "'");
        
        // Eliminar etiqueta
        $this->db->query("DELETE FROM " . DB_PREFIX . "blog_tag WHERE tag_id = '" . (int)$tag_id . "'");
    }

    public function getTag($tag_id) {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "blog_tag WHERE tag_id = '" . (int)$tag_id . "'");

        return $query->row;
    }

    public function getBlogTags($data = array()) {
        $sql = "SELECT * FROM " . DB_PREFIX . "blog_tag";

        // Aplicar filtros
        $where = array();

        if (!empty($data['filter_name'])) {
            $where[] = "name LIKE '%" . $this->db->escape($data['filter_name']) . "%'";
        }

        // Añadir cláusula WHERE
        if ($where) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }

        // Ordenar
        $sql .= " ORDER BY name";

        $query = $this->db->query($sql);

        return $query->rows;
    }

    public function getTotalBlogTags() {
        $query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "blog_tag");

        return $query->row['total'];
    }

    public function isSlugExists($slug, $tag_id = 0) {
        $query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "blog_tag WHERE slug = '" . $this->db->escape($slug) . "' AND tag_id != '" . (int)$tag_id . "'");
        
        return $query->row['total'] > 0;
    }

    public function generateSlug($text) {
        // Convertir a minúsculas
        $text = mb_strtolower($text, 'UTF-8');
        
        // Reemplazar espacios con guiones
        $text = preg_replace('/\s+/', '-', $text);
        
        // Eliminar caracteres especiales
        $text = preg_replace('/[^a-z0-9\-]/', '', $text);
        
        // Eliminar guiones duplicados
        $text = preg_replace('/-+/', '-', $text);
        
        // Eliminar guiones al principio y al final
        $text = trim($text, '-');
        
        return $text;
    }
}