<?php
class ModelCatalogBlogComment extends Model {
    public function addComment($data) {
        $this->db->query("INSERT INTO " . DB_PREFIX . "blog_comment SET 
            post_id = '" . (int)$data['post_id'] . "', 
            parent_id = '" . (int)$data['parent_id'] . "', 
            author = '" . $this->db->escape($data['author']) . "', 
            email = '" . $this->db->escape($data['email']) . "', 
            website = '" . $this->db->escape($data['website']) . "', 
            content = '" . $this->db->escape($data['content']) . "', 
            status = '" . (int)$data['status'] . "', 
            notify = '" . (isset($data['notify']) ? (int)$data['notify'] : 0) . "', 
            ip = '" . $this->db->escape($data['ip']) . "', 
            date_added = NOW(), 
            date_modified = NOW()");

        return $this->db->getLastId();
    }

    public function editComment($comment_id, $data) {
        $this->db->query("UPDATE " . DB_PREFIX . "blog_comment SET 
            post_id = '" . (int)$data['post_id'] . "', 
            parent_id = '" . (int)$data['parent_id'] . "', 
            author = '" . $this->db->escape($data['author']) . "', 
            email = '" . $this->db->escape($data['email']) . "', 
            website = '" . $this->db->escape($data['website']) . "', 
            content = '" . $this->db->escape($data['content']) . "', 
            status = '" . (int)$data['status'] . "', 
            notify = '" . (isset($data['notify']) ? (int)$data['notify'] : 0) . "', 
            date_modified = NOW() 
            WHERE comment_id = '" . (int)$comment_id . "'");
    }

    public function deleteComment($comment_id) {
        // Eliminar comentario
        $this->db->query("DELETE FROM " . DB_PREFIX . "blog_comment WHERE comment_id = '" . (int)$comment_id . "'");
        
        // Eliminar comentarios hijo (respuestas)
        $query = $this->db->query("SELECT comment_id FROM " . DB_PREFIX . "blog_comment WHERE parent_id = '" . (int)$comment_id . "'");
        
        foreach ($query->rows as $result) {
            $this->deleteComment($result['comment_id']);
        }
    }

    public function getComment($comment_id) {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "blog_comment WHERE comment_id = '" . (int)$comment_id . "'");

        return $query->row;
    }

    public function getComments($data = array()) {
        $sql = "SELECT c.*, p.title AS post_title FROM " . DB_PREFIX . "blog_comment c 
                LEFT JOIN " . DB_PREFIX . "blog_post p ON (c.post_id = p.post_id)";

        $where = array();

        if (!empty($data['filter_post_id'])) {
            $where[] = "c.post_id = '" . (int)$data['filter_post_id'] . "'";
        }

        if (!empty($data['filter_author'])) {
            $where[] = "LOWER(c.author) LIKE '%" . $this->db->escape(utf8_strtolower($data['filter_author'])) . "%'";
        }

        if (!empty($data['filter_email'])) {
            $where[] = "LOWER(c.email) LIKE '%" . $this->db->escape(utf8_strtolower($data['filter_email'])) . "%'";
        }

        if (isset($data['filter_status']) && $data['filter_status'] !== '') {
            $where[] = "c.status = '" . (int)$data['filter_status'] . "'";
        }

        if (!empty($data['filter_date_added'])) {
            $where[] = "DATE(c.date_added) = DATE('" . $this->db->escape($data['filter_date_added']) . "')";
        }

        if ($where) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }

        $sql .= " ORDER BY c.date_added DESC";

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

    public function getTotalComments($data = array()) {
        $sql = "SELECT COUNT(*) AS total FROM " . DB_PREFIX . "blog_comment c";

        $where = array();

        if (!empty($data['filter_post_id'])) {
            $where[] = "c.post_id = '" . (int)$data['filter_post_id'] . "'";
        }

        if (!empty($data['filter_author'])) {
            $where[] = "LOWER(c.author) LIKE '%" . $this->db->escape(utf8_strtolower($data['filter_author'])) . "%'";
        }

        if (!empty($data['filter_email'])) {
            $where[] = "LOWER(c.email) LIKE '%" . $this->db->escape(utf8_strtolower($data['filter_email'])) . "%'";
        }

        if (isset($data['filter_status']) && $data['filter_status'] !== '') {
            $where[] = "c.status = '" . (int)$data['filter_status'] . "'";
        }

        if (!empty($data['filter_date_added'])) {
            $where[] = "DATE(c.date_added) = DATE('" . $this->db->escape($data['filter_date_added']) . "')";
        }

        if ($where) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }

        $query = $this->db->query($sql);

        return $query->row['total'];
    }

    public function getCommentReplies($comment_id) {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "blog_comment WHERE parent_id = '" . (int)$comment_id . "' ORDER BY date_added ASC");

        return $query->rows;
    }

    public function getTotalCommentsByPostId($post_id) {
        $query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "blog_comment WHERE post_id = '" . (int)$post_id . "'");

        return $query->row['total'];
    }
}