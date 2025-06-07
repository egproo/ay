<?php
class ModelCatalogBlog extends Model {
    public function addPost($data) {
        // Generación del slug si está vacío
        if (empty($data['slug'])) {
            $data['slug'] = $this->generateSlug($data['title']);
        }

        $this->db->query("INSERT INTO " . DB_PREFIX . "blog_post SET 
            author_id = '" . (int)$this->user->getId() . "', 
            title = '" . $this->db->escape($data['title']) . "', 
            slug = '" . $this->db->escape($data['slug']) . "', 
            short_description = '" . $this->db->escape($data['short_description']) . "', 
            content = '" . $this->db->escape($data['content']) . "', 
            meta_title = '" . $this->db->escape($data['meta_title']) . "', 
            meta_description = '" . $this->db->escape($data['meta_description']) . "', 
            meta_keywords = '" . $this->db->escape($data['meta_keywords']) . "', 
            featured_image = '" . $this->db->escape($data['featured_image']) . "', 
            status = '" . (int)$data['status'] . "', 
            comment_status = '" . (int)$data['comment_status'] . "', 
            sort_order = '" . (int)$data['sort_order'] . "', 
            date_published = '" . $this->db->escape($data['date_published']) . "', 
            date_created = NOW(), 
            date_modified = NOW()");

        $post_id = $this->db->getLastId();

        // Guardar categorías
        if (isset($data['post_category'])) {
            foreach ($data['post_category'] as $category_id) {
                $this->db->query("INSERT INTO " . DB_PREFIX . "blog_post_to_category SET post_id = '" . (int)$post_id . "', category_id = '" . (int)$category_id . "'");
            }
        }

        // Guardar etiquetas
        if (isset($data['post_tag'])) {
            foreach ($data['post_tag'] as $tag) {
                // Verificar si la etiqueta ya existe
                $tag_query = $this->db->query("SELECT tag_id FROM " . DB_PREFIX . "blog_tag WHERE LOWER(name) = LOWER('" . $this->db->escape($tag['name']) . "')");
                
                if ($tag_query->num_rows) {
                    $tag_id = $tag_query->row['tag_id'];
                } else {
                    // Crear nueva etiqueta
                    $this->db->query("INSERT INTO " . DB_PREFIX . "blog_tag SET 
                        name = '" . $this->db->escape($tag['name']) . "', 
                        slug = '" . $this->db->escape($this->generateSlug($tag['name'])) . "', 
                        date_added = NOW(), 
                        date_modified = NOW()");
                        
                    $tag_id = $this->db->getLastId();
                }
                
                // Asociar etiqueta al post
                $this->db->query("INSERT INTO " . DB_PREFIX . "blog_post_to_tag SET post_id = '" . (int)$post_id . "', tag_id = '" . (int)$tag_id . "'");
            }
        }

        return $post_id;
    }

    public function editPost($post_id, $data) {
        // Generación del slug si está vacío
        if (empty($data['slug'])) {
            $data['slug'] = $this->generateSlug($data['title']);
        }

        $this->db->query("UPDATE " . DB_PREFIX . "blog_post SET 
            title = '" . $this->db->escape($data['title']) . "', 
            slug = '" . $this->db->escape($data['slug']) . "', 
            short_description = '" . $this->db->escape($data['short_description']) . "', 
            content = '" . $this->db->escape($data['content']) . "', 
            meta_title = '" . $this->db->escape($data['meta_title']) . "', 
            meta_description = '" . $this->db->escape($data['meta_description']) . "', 
            meta_keywords = '" . $this->db->escape($data['meta_keywords']) . "', 
            featured_image = '" . $this->db->escape($data['featured_image']) . "', 
            status = '" . (int)$data['status'] . "', 
            comment_status = '" . (int)$data['comment_status'] . "', 
            sort_order = '" . (int)$data['sort_order'] . "', 
            date_published = '" . $this->db->escape($data['date_published']) . "', 
            date_modified = NOW() 
            WHERE post_id = '" . (int)$post_id . "'");

        // Eliminar categorías antiguas
        $this->db->query("DELETE FROM " . DB_PREFIX . "blog_post_to_category WHERE post_id = '" . (int)$post_id . "'");

        // Guardar categorías nuevas
        if (isset($data['post_category'])) {
            foreach ($data['post_category'] as $category_id) {
                $this->db->query("INSERT INTO " . DB_PREFIX . "blog_post_to_category SET post_id = '" . (int)$post_id . "', category_id = '" . (int)$category_id . "'");
            }
        }

        // Eliminar etiquetas antiguas
        $this->db->query("DELETE FROM " . DB_PREFIX . "blog_post_to_tag WHERE post_id = '" . (int)$post_id . "'");

        // Guardar etiquetas nuevas
        if (isset($data['post_tag'])) {
            foreach ($data['post_tag'] as $tag) {
                // Verificar si la etiqueta ya existe
                $tag_query = $this->db->query("SELECT tag_id FROM " . DB_PREFIX . "blog_tag WHERE LOWER(name) = LOWER('" . $this->db->escape($tag['name']) . "')");
                
                if ($tag_query->num_rows) {
                    $tag_id = $tag_query->row['tag_id'];
                } else {
                    // Crear nueva etiqueta
                    $this->db->query("INSERT INTO " . DB_PREFIX . "blog_tag SET 
                        name = '" . $this->db->escape($tag['name']) . "', 
                        slug = '" . $this->db->escape($this->generateSlug($tag['name'])) . "', 
                        date_added = NOW(), 
                        date_modified = NOW()");
                        
                    $tag_id = $this->db->getLastId();
                }
                
                // Asociar etiqueta al post
                $this->db->query("INSERT INTO " . DB_PREFIX . "blog_post_to_tag SET post_id = '" . (int)$post_id . "', tag_id = '" . (int)$tag_id . "'");
            }
        }
    }

    public function deletePost($post_id) {
        // Eliminar post
        $this->db->query("DELETE FROM " . DB_PREFIX . "blog_post WHERE post_id = '" . (int)$post_id . "'");
        
        // Eliminar relaciones con categorías
        $this->db->query("DELETE FROM " . DB_PREFIX . "blog_post_to_category WHERE post_id = '" . (int)$post_id . "'");
        
        // Eliminar relaciones con etiquetas
        $this->db->query("DELETE FROM " . DB_PREFIX . "blog_post_to_tag WHERE post_id = '" . (int)$post_id . "'");
        
        // Eliminar comentarios
        $this->db->query("DELETE FROM " . DB_PREFIX . "blog_comment WHERE post_id = '" . (int)$post_id . "'");
    }

    public function copyPost($post_id) {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "blog_post WHERE post_id = '" . (int)$post_id . "'");

        if ($query->num_rows) {
            $data = $query->row;
            
            $data['status'] = 0; // Poner como borrador
            $data['title'] = $data['title'] . ' (copia)';
            $data['slug'] = $this->generateSlug($data['title']);
            
            // Obtener categorías
            $data['post_category'] = $this->getPostCategories($post_id);
            
            // Obtener etiquetas
            $data['post_tag'] = $this->getPostTags($post_id);
            
            // Añadir nueva entrada
            $this->addPost($data);
        }
    }

    public function getPost($post_id) {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "blog_post WHERE post_id = '" . (int)$post_id . "'");

        return $query->row;
    }

    public function getPosts($data = array()) {
        $sql = "SELECT p.*, u.username as author_username FROM " . DB_PREFIX . "blog_post p LEFT JOIN " . DB_PREFIX . "user u ON (p.author_id = u.user_id)";

        $join_category = false;
        
        // Aplicar filtros
        $where = array();

        if (!empty($data['filter_title'])) {
            $where[] = "p.title LIKE '%" . $this->db->escape($data['filter_title']) . "%'";
        }

        if (isset($data['filter_status']) && $data['filter_status'] !== '') {
            $where[] = "p.status = '" . (int)$data['filter_status'] . "'";
        }

        if (!empty($data['filter_date_start'])) {
            $where[] = "DATE(p.date_published) >= '" . $this->db->escape($data['filter_date_start']) . "'";
        }

        if (!empty($data['filter_date_end'])) {
            $where[] = "DATE(p.date_published) <= '" . $this->db->escape($data['filter_date_end']) . "'";
        }

        if (!empty($data['filter_category'])) {
            $join_category = true;
            $where[] = "pc.category_id = '" . (int)$data['filter_category'] . "'";
        }

        // Añadir JOIN para filtrar por categoría si es necesario
        if ($join_category) {
            $sql .= " LEFT JOIN " . DB_PREFIX . "blog_post_to_category pc ON (p.post_id = pc.post_id)";
        }

        // Añadir cláusula WHERE
        if ($where) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }

        // Ordenar y agrupar
        $sql .= " GROUP BY p.post_id";

        if (isset($data['sort'])) {
            $sql .= " ORDER BY " . $data['sort'];
        } else {
            $sql .= " ORDER BY p.date_published";
        }

        if (isset($data['order']) && ($data['order'] == 'ASC')) {
            $sql .= " ASC";
        } else {
            $sql .= " DESC";
        }

        // Paginación
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

    public function getTotalPosts($data = array()) {
        $sql = "SELECT COUNT(DISTINCT p.post_id) AS total FROM " . DB_PREFIX . "blog_post p";

        $join_category = false;
        
        // Aplicar filtros
        $where = array();

        if (!empty($data['filter_title'])) {
            $where[] = "p.title LIKE '%" . $this->db->escape($data['filter_title']) . "%'";
        }

        if (isset($data['filter_status']) && $data['filter_status'] !== '') {
            $where[] = "p.status = '" . (int)$data['filter_status'] . "'";
        }

        if (!empty($data['filter_date_start'])) {
            $where[] = "DATE(p.date_published) >= '" . $this->db->escape($data['filter_date_start']) . "'";
        }

        if (!empty($data['filter_date_end'])) {
            $where[] = "DATE(p.date_published) <= '" . $this->db->escape($data['filter_date_end']) . "'";
        }

        if (!empty($data['filter_category'])) {
            $join_category = true;
            $where[] = "pc.category_id = '" . (int)$data['filter_category'] . "'";
        }

        // Añadir JOIN para filtrar por categoría si es necesario
        if ($join_category) {
            $sql .= " LEFT JOIN " . DB_PREFIX . "blog_post_to_category pc ON (p.post_id = pc.post_id)";
        }

        // Añadir cláusula WHERE
        if ($where) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }

        $query = $this->db->query($sql);

        return $query->row['total'];
    }

    public function getPostCategories($post_id) {
        $post_category_data = array();

        $query = $this->db->query("SELECT pc.category_id, c.name FROM " . DB_PREFIX . "blog_post_to_category pc LEFT JOIN " . DB_PREFIX . "blog_category c ON (pc.category_id = c.category_id) WHERE pc.post_id = '" . (int)$post_id . "'");

        foreach ($query->rows as $result) {
            $post_category_data[] = array(
                'category_id' => $result['category_id'],
                'name' => $result['name']
            );
        }

        return $post_category_data;
    }

    public function getPostTags($post_id) {
        $post_tag_data = array();

        $query = $this->db->query("SELECT pt.tag_id, t.name FROM " . DB_PREFIX . "blog_post_to_tag pt LEFT JOIN " . DB_PREFIX . "blog_tag t ON (pt.tag_id = t.tag_id) WHERE pt.post_id = '" . (int)$post_id . "'");

        foreach ($query->rows as $result) {
            $post_tag_data[] = array(
                'tag_id' => $result['tag_id'],
                'name' => $result['name']
            );
        }

        return $post_tag_data;
    }

    public function getPostCommentsCount($post_id) {
        $query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "blog_comment WHERE post_id = '" . (int)$post_id . "'");

        return $query->row['total'];
    }

    public function getTotalComments($data = array()) {
        $sql = "SELECT COUNT(*) AS total FROM " . DB_PREFIX . "blog_comment";

        // Aplicar filtros
        $where = array();

        if (isset($data['filter_status']) && $data['filter_status'] !== '') {
            $where[] = "status = '" . (int)$data['filter_status'] . "'";
        }

        if (!empty($data['filter_post_id'])) {
            $where[] = "post_id = '" . (int)$data['filter_post_id'] . "'";
        }

        // Añadir cláusula WHERE
        if ($where) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }

        $query = $this->db->query($sql);

        return $query->row['total'];
    }

    public function isSlugExists($slug, $post_id = 0) {
        $query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "blog_post WHERE slug = '" . $this->db->escape($slug) . "' AND post_id != '" . (int)$post_id . "'");
        
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