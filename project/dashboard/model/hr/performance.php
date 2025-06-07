<?php
class ModelHrPerformance extends Model {

    public function getTotalReviews($filter_data = array()) {
        $sql = "SELECT COUNT(*) AS total FROM `cod_performance_review` pr
                LEFT JOIN `cod_user` u ON (pr.user_id = u.user_id)
                LEFT JOIN `cod_user` ur ON (pr.reviewer_id = ur.user_id)
                WHERE 1";

        if (!empty($filter_data['filter_user'])) {
            $sql .= " AND pr.user_id = '" . (int)$filter_data['filter_user'] . "'";
        }

        if (!empty($filter_data['filter_date_start'])) {
            $sql .= " AND pr.review_date >= '" . $this->db->escape($filter_data['filter_date_start']) . "'";
        }

        if (!empty($filter_data['filter_date_end'])) {
            $sql .= " AND pr.review_date <= '" . $this->db->escape($filter_data['filter_date_end']) . "'";
        }

        if (!empty($filter_data['filter_status'])) {
            $sql .= " AND pr.status = '" . $this->db->escape($filter_data['filter_status']) . "'";
        }

        $query = $this->db->query($sql);
        return $query->row['total'];
    }

    public function getReviews($filter_data = array()) {
        $sql = "SELECT pr.*, 
                CONCAT(u.firstname,' ',u.lastname) as employee_name,
                CONCAT(ur.firstname,' ',ur.lastname) as reviewer_name
                FROM `cod_performance_review` pr
                LEFT JOIN `cod_user` u ON (pr.user_id = u.user_id)
                LEFT JOIN `cod_user` ur ON (pr.reviewer_id = ur.user_id)
                WHERE 1";

        if (!empty($filter_data['filter_user'])) {
            $sql .= " AND pr.user_id = '" . (int)$filter_data['filter_user'] . "'";
        }

        if (!empty($filter_data['filter_date_start'])) {
            $sql .= " AND pr.review_date >= '" . $this->db->escape($filter_data['filter_date_start']) . "'";
        }

        if (!empty($filter_data['filter_date_end'])) {
            $sql .= " AND pr.review_date <= '" . $this->db->escape($filter_data['filter_date_end']) . "'";
        }

        if (!empty($filter_data['filter_status'])) {
            $sql .= " AND pr.status = '" . $this->db->escape($filter_data['filter_status']) . "'";
        }

        $sort_data = array('employee_name','review_date','reviewer_name','overall_score','status');
        $sort = (isset($filter_data['sort']) && in_array($filter_data['sort'], $sort_data)) ? $filter_data['sort'] : 'review_date';
        $order = (isset($filter_data['order']) && $filter_data['order'] == 'desc') ? 'DESC' : 'ASC';

        $sql .= " ORDER BY " . $sort . " " . $order;

        $start = isset($filter_data['start']) ? (int)$filter_data['start'] : 0;
        $limit = isset($filter_data['limit']) ? (int)$filter_data['limit'] : 10;

        if ($start < 0) { $start = 0; }
        if ($limit < 1) { $limit = 10; }

        $sql .= " LIMIT " . $start . "," . $limit;

        $query = $this->db->query($sql);
        return $query->rows;
    }

    public function getReviewById($review_id) {
        $query = $this->db->query("SELECT * FROM `cod_performance_review` WHERE review_id = '" . (int)$review_id . "'");
        return $query->row;
    }

    public function addReview($data) {
        $this->db->query("INSERT INTO `cod_performance_review` SET 
            user_id = '" . (int)$data['user_id'] . "',
            reviewer_id = '" . (int)$data['reviewer_id'] . "',
            review_date = '" . $this->db->escape($data['review_date']) . "',
            overall_score = '" . (float)$data['overall_score'] . "',
            status = '" . $this->db->escape($data['status']) . "',
            comments = '" . $this->db->escape($data['comments']) . "'");

        $review_id = $this->db->getLastId();

        // حفظ المعايير
        if (!empty($data['criteria_score'])) {
            foreach ($data['criteria_score'] as $criteria_id => $score) {
                $criteria_comments = isset($data['criteria_comments'][$criteria_id]) ? $data['criteria_comments'][$criteria_id] : '';
                $this->db->query("INSERT INTO `cod_performance_review_criteria` SET 
                    review_id = '" . (int)$review_id . "',
                    criteria_id = '" . (int)$criteria_id . "',
                    score = '" . (float)$score . "',
                    comments = '" . $this->db->escape($criteria_comments) . "'");
            }
        }

        return $review_id;
    }

    public function editReview($review_id, $data) {
        $this->db->query("UPDATE `cod_performance_review` SET 
            user_id = '" . (int)$data['user_id'] . "',
            reviewer_id = '" . (int)$data['reviewer_id'] . "',
            review_date = '" . $this->db->escape($data['review_date']) . "',
            overall_score = '" . (float)$data['overall_score'] . "',
            status = '" . $this->db->escape($data['status']) . "',
            comments = '" . $this->db->escape($data['comments']) . "'
            WHERE review_id = '" . (int)$review_id . "'");

        // تحديث المعايير: احذف القديمة وأضف الجديدة
        $this->db->query("DELETE FROM `cod_performance_review_criteria` WHERE review_id = '" . (int)$review_id . "'");

        if (!empty($data['criteria_score'])) {
            foreach ($data['criteria_score'] as $criteria_id => $score) {
                $criteria_comments = isset($data['criteria_comments'][$criteria_id]) ? $data['criteria_comments'][$criteria_id] : '';
                $this->db->query("INSERT INTO `cod_performance_review_criteria` SET 
                    review_id = '" . (int)$review_id . "',
                    criteria_id = '" . (int)$criteria_id . "',
                    score = '" . (float)$score . "',
                    comments = '" . $this->db->escape($criteria_comments) . "'");
            }
        }
    }

    public function deleteReview($review_id) {
        $this->db->query("DELETE FROM `cod_performance_review` WHERE review_id = '" . (int)$review_id . "'");
        $this->db->query("DELETE FROM `cod_performance_review_criteria` WHERE review_id = '" . (int)$review_id . "'");
    }

    public function getReviewCriteria($review_id) {
        // إذا كان review_id=0 يعني تقييم جديد، نعرض جميع المعايير الفعّالة بدون نتائج
        // إذا كان review_id>0 يعني تقييم موجود ونحضر المعايير مع الدرجة والتعليق
        if ($review_id > 0) {
            $sql = "SELECT c.criteria_id, c.name, rc.score, rc.comments
                    FROM `cod_performance_criteria` c
                    LEFT JOIN `cod_performance_review_criteria` rc ON (c.criteria_id = rc.criteria_id AND rc.review_id = '" . (int)$review_id . "')
                    WHERE c.status = '1'
                    ORDER BY c.name";
        } else {
            $sql = "SELECT c.criteria_id, c.name, '' as score, '' as comments
                    FROM `cod_performance_criteria` c
                    WHERE c.status = '1'
                    ORDER BY c.name";
        }

        $query = $this->db->query($sql);
        return $query->rows;
    }

}
