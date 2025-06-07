<?php
class ModelSupplierEvaluation extends Model {

    /**
     * إضافة تقييم مورد جديد
     */
    public function addEvaluation($data) {
        // حساب النتيجة الإجمالية
        $overall_score = ($data['quality_score'] + $data['delivery_score'] + $data['price_score'] + $data['service_score']) / 4;

        $this->db->query("INSERT INTO " . DB_PREFIX . "supplier_evaluation SET 
                         supplier_id = '" . (int)$data['supplier_id'] . "',
                         evaluator_id = '" . (int)$this->user->getId() . "',
                         evaluation_date = '" . $this->db->escape($data['evaluation_date']) . "',
                         quality_score = '" . (float)$data['quality_score'] . "',
                         delivery_score = '" . (float)$data['delivery_score'] . "',
                         price_score = '" . (float)$data['price_score'] . "',
                         service_score = '" . (float)$data['service_score'] . "',
                         overall_score = '" . (float)$overall_score . "',
                         comments = '" . $this->db->escape($data['comments']) . "'");

        $evaluation_id = $this->db->getLastId();

        // تحديث متوسط تقييم المورد
        $this->updateSupplierAverageRating($data['supplier_id']);

        return $evaluation_id;
    }

    /**
     * تعديل تقييم مورد
     */
    public function editEvaluation($evaluation_id, $data) {
        // حساب النتيجة الإجمالية
        $overall_score = ($data['quality_score'] + $data['delivery_score'] + $data['price_score'] + $data['service_score']) / 4;

        $this->db->query("UPDATE " . DB_PREFIX . "supplier_evaluation SET 
                         supplier_id = '" . (int)$data['supplier_id'] . "',
                         evaluation_date = '" . $this->db->escape($data['evaluation_date']) . "',
                         quality_score = '" . (float)$data['quality_score'] . "',
                         delivery_score = '" . (float)$data['delivery_score'] . "',
                         price_score = '" . (float)$data['price_score'] . "',
                         service_score = '" . (float)$data['service_score'] . "',
                         overall_score = '" . (float)$overall_score . "',
                         comments = '" . $this->db->escape($data['comments']) . "'
                         WHERE evaluation_id = '" . (int)$evaluation_id . "'");

        // تحديث متوسط تقييم المورد
        $this->updateSupplierAverageRating($data['supplier_id']);
    }

    /**
     * حذف تقييم مورد
     */
    public function deleteEvaluation($evaluation_id) {
        // الحصول على معرف المورد قبل الحذف
        $evaluation = $this->getEvaluation($evaluation_id);
        $supplier_id = $evaluation['supplier_id'];

        $this->db->query("DELETE FROM " . DB_PREFIX . "supplier_evaluation WHERE evaluation_id = '" . (int)$evaluation_id . "'");

        // تحديث متوسط تقييم المورد
        if ($supplier_id) {
            $this->updateSupplierAverageRating($supplier_id);
        }
    }

    /**
     * الحصول على تقييم مورد
     */
    public function getEvaluation($evaluation_id) {
        $query = $this->db->query("SELECT se.*, 
                                          s.firstname AS supplier_firstname, s.lastname AS supplier_lastname,
                                          CONCAT(s.firstname, ' ', COALESCE(s.lastname, '')) AS supplier_name,
                                          u.firstname AS evaluator_firstname, u.lastname AS evaluator_lastname,
                                          CONCAT(u.firstname, ' ', COALESCE(u.lastname, '')) AS evaluator_name
                                   FROM " . DB_PREFIX . "supplier_evaluation se
                                   LEFT JOIN " . DB_PREFIX . "supplier s ON (se.supplier_id = s.supplier_id)
                                   LEFT JOIN " . DB_PREFIX . "user u ON (se.evaluator_id = u.user_id)
                                   WHERE se.evaluation_id = '" . (int)$evaluation_id . "'");

        return $query->row;
    }

    /**
     * الحصول على قائمة تقييمات الموردين
     */
    public function getEvaluations($data = array()) {
        $sql = "SELECT se.*, 
                       s.firstname AS supplier_firstname, s.lastname AS supplier_lastname,
                       CONCAT(s.firstname, ' ', COALESCE(s.lastname, '')) AS supplier_name,
                       u.firstname AS evaluator_firstname, u.lastname AS evaluator_lastname,
                       CONCAT(u.firstname, ' ', COALESCE(u.lastname, '')) AS evaluator_name
                FROM " . DB_PREFIX . "supplier_evaluation se
                LEFT JOIN " . DB_PREFIX . "supplier s ON (se.supplier_id = s.supplier_id)
                LEFT JOIN " . DB_PREFIX . "user u ON (se.evaluator_id = u.user_id)
                WHERE 1 = 1";

        if (!empty($data['filter_supplier_id'])) {
            $sql .= " AND se.supplier_id = '" . (int)$data['filter_supplier_id'] . "'";
        }

        if (!empty($data['filter_evaluator_id'])) {
            $sql .= " AND se.evaluator_id = '" . (int)$data['filter_evaluator_id'] . "'";
        }

        if (!empty($data['filter_date_start'])) {
            $sql .= " AND DATE(se.evaluation_date) >= '" . $this->db->escape($data['filter_date_start']) . "'";
        }

        if (!empty($data['filter_date_end'])) {
            $sql .= " AND DATE(se.evaluation_date) <= '" . $this->db->escape($data['filter_date_end']) . "'";
        }

        $sort_data = array(
            'se.evaluation_id',
            'supplier_name',
            'evaluator_name',
            'se.evaluation_date',
            'se.quality_score',
            'se.delivery_score',
            'se.price_score',
            'se.service_score',
            'se.overall_score'
        );

        if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
            $sql .= " ORDER BY " . $data['sort'];
        } else {
            $sql .= " ORDER BY se.evaluation_date";
        }

        if (isset($data['order']) && ($data['order'] == 'ASC')) {
            $sql .= " ASC";
        } else {
            $sql .= " DESC";
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
     * الحصول على إجمالي عدد تقييمات الموردين
     */
    public function getTotalEvaluations($data = array()) {
        $sql = "SELECT COUNT(*) AS total FROM " . DB_PREFIX . "supplier_evaluation se
                LEFT JOIN " . DB_PREFIX . "supplier s ON (se.supplier_id = s.supplier_id)
                LEFT JOIN " . DB_PREFIX . "user u ON (se.evaluator_id = u.user_id)
                WHERE 1 = 1";

        if (!empty($data['filter_supplier_id'])) {
            $sql .= " AND se.supplier_id = '" . (int)$data['filter_supplier_id'] . "'";
        }

        if (!empty($data['filter_evaluator_id'])) {
            $sql .= " AND se.evaluator_id = '" . (int)$data['filter_evaluator_id'] . "'";
        }

        if (!empty($data['filter_date_start'])) {
            $sql .= " AND DATE(se.evaluation_date) >= '" . $this->db->escape($data['filter_date_start']) . "'";
        }

        if (!empty($data['filter_date_end'])) {
            $sql .= " AND DATE(se.evaluation_date) <= '" . $this->db->escape($data['filter_date_end']) . "'";
        }

        $query = $this->db->query($sql);

        return $query->row['total'];
    }

    /**
     * تحديث متوسط تقييم المورد
     */
    public function updateSupplierAverageRating($supplier_id) {
        $query = $this->db->query("SELECT 
                                   AVG(quality_score) as avg_quality,
                                   AVG(delivery_score) as avg_delivery,
                                   AVG(price_score) as avg_price,
                                   AVG(service_score) as avg_service,
                                   AVG(overall_score) as avg_overall,
                                   COUNT(*) as total_evaluations
                                   FROM " . DB_PREFIX . "supplier_evaluation 
                                   WHERE supplier_id = '" . (int)$supplier_id . "'");

        if ($query->num_rows) {
            $avg_rating = $query->row['avg_overall'] ? $query->row['avg_overall'] : 0;
            $total_evaluations = $query->row['total_evaluations'];

            // تحديث جدول الموردين بالمتوسط الجديد
            $this->db->query("UPDATE " . DB_PREFIX . "supplier SET 
                             average_rating = '" . (float)$avg_rating . "',
                             total_evaluations = '" . (int)$total_evaluations . "'
                             WHERE supplier_id = '" . (int)$supplier_id . "'");
        }
    }

    /**
     * الحصول على تقرير تقييم مورد
     */
    public function getSupplierEvaluationReport($supplier_id) {
        $query = $this->db->query("SELECT 
                                   s.firstname, s.lastname, s.email, s.telephone,
                                   AVG(se.quality_score) as avg_quality,
                                   AVG(se.delivery_score) as avg_delivery,
                                   AVG(se.price_score) as avg_price,
                                   AVG(se.service_score) as avg_service,
                                   AVG(se.overall_score) as avg_overall,
                                   COUNT(se.evaluation_id) as total_evaluations,
                                   MAX(se.evaluation_date) as last_evaluation_date,
                                   MIN(se.evaluation_date) as first_evaluation_date
                                   FROM " . DB_PREFIX . "supplier s
                                   LEFT JOIN " . DB_PREFIX . "supplier_evaluation se ON (s.supplier_id = se.supplier_id)
                                   WHERE s.supplier_id = '" . (int)$supplier_id . "'
                                   GROUP BY s.supplier_id");

        return $query->row;
    }

    /**
     * الحصول على تاريخ تقييمات مورد
     */
    public function getSupplierEvaluationHistory($supplier_id) {
        $query = $this->db->query("SELECT se.*, 
                                          u.firstname AS evaluator_firstname, u.lastname AS evaluator_lastname,
                                          CONCAT(u.firstname, ' ', COALESCE(u.lastname, '')) AS evaluator_name
                                   FROM " . DB_PREFIX . "supplier_evaluation se
                                   LEFT JOIN " . DB_PREFIX . "user u ON (se.evaluator_id = u.user_id)
                                   WHERE se.supplier_id = '" . (int)$supplier_id . "'
                                   ORDER BY se.evaluation_date DESC");

        return $query->rows;
    }

    /**
     * الحصول على أفضل الموردين حسب التقييم
     */
    public function getTopRatedSuppliers($limit = 10) {
        $query = $this->db->query("SELECT s.supplier_id, 
                                          CONCAT(s.firstname, ' ', COALESCE(s.lastname, '')) AS supplier_name,
                                          AVG(se.overall_score) as avg_rating,
                                          COUNT(se.evaluation_id) as total_evaluations
                                   FROM " . DB_PREFIX . "supplier s
                                   INNER JOIN " . DB_PREFIX . "supplier_evaluation se ON (s.supplier_id = se.supplier_id)
                                   GROUP BY s.supplier_id
                                   HAVING total_evaluations >= 3
                                   ORDER BY avg_rating DESC, total_evaluations DESC
                                   LIMIT " . (int)$limit);

        return $query->rows;
    }

    /**
     * الحصول على إحصائيات التقييمات
     */
    public function getEvaluationStatistics() {
        $statistics = array();

        // إجمالي التقييمات
        $query = $this->db->query("SELECT COUNT(*) as total FROM " . DB_PREFIX . "supplier_evaluation");
        $statistics['total_evaluations'] = $query->row['total'];

        // متوسط التقييمات العام
        $query = $this->db->query("SELECT 
                                   AVG(quality_score) as avg_quality,
                                   AVG(delivery_score) as avg_delivery,
                                   AVG(price_score) as avg_price,
                                   AVG(service_score) as avg_service,
                                   AVG(overall_score) as avg_overall
                                   FROM " . DB_PREFIX . "supplier_evaluation");
        $statistics['averages'] = $query->row;

        // عدد الموردين المقيمين
        $query = $this->db->query("SELECT COUNT(DISTINCT supplier_id) as total FROM " . DB_PREFIX . "supplier_evaluation");
        $statistics['evaluated_suppliers'] = $query->row['total'];

        // التقييمات حسب الشهر الحالي
        $query = $this->db->query("SELECT COUNT(*) as total FROM " . DB_PREFIX . "supplier_evaluation 
                                   WHERE MONTH(evaluation_date) = MONTH(CURDATE()) 
                                   AND YEAR(evaluation_date) = YEAR(CURDATE())");
        $statistics['this_month_evaluations'] = $query->row['total'];

        return $statistics;
    }

    /**
     * البحث في التقييمات
     */
    public function searchEvaluations($search_term) {
        $query = $this->db->query("SELECT se.*, 
                                          CONCAT(s.firstname, ' ', COALESCE(s.lastname, '')) AS supplier_name,
                                          CONCAT(u.firstname, ' ', COALESCE(u.lastname, '')) AS evaluator_name
                                   FROM " . DB_PREFIX . "supplier_evaluation se
                                   LEFT JOIN " . DB_PREFIX . "supplier s ON (se.supplier_id = s.supplier_id)
                                   LEFT JOIN " . DB_PREFIX . "user u ON (se.evaluator_id = u.user_id)
                                   WHERE CONCAT(s.firstname, ' ', COALESCE(s.lastname, '')) LIKE '%" . $this->db->escape($search_term) . "%'
                                   OR se.comments LIKE '%" . $this->db->escape($search_term) . "%'
                                   ORDER BY se.evaluation_date DESC
                                   LIMIT 10");

        return $query->rows;
    }

    /**
     * الحصول على تقييمات مورد في فترة معينة
     */
    public function getSupplierEvaluationsByPeriod($supplier_id, $start_date, $end_date) {
        $query = $this->db->query("SELECT se.*, 
                                          u.firstname AS evaluator_firstname, u.lastname AS evaluator_lastname,
                                          CONCAT(u.firstname, ' ', COALESCE(u.lastname, '')) AS evaluator_name
                                   FROM " . DB_PREFIX . "supplier_evaluation se
                                   LEFT JOIN " . DB_PREFIX . "user u ON (se.evaluator_id = u.user_id)
                                   WHERE se.supplier_id = '" . (int)$supplier_id . "'
                                   AND se.evaluation_date BETWEEN '" . $this->db->escape($start_date) . "' AND '" . $this->db->escape($end_date) . "'
                                   ORDER BY se.evaluation_date DESC");

        return $query->rows;
    }

    /**
     * مقارنة تقييمات الموردين
     */
    public function compareSuppliers($supplier_ids) {
        if (empty($supplier_ids) || !is_array($supplier_ids)) {
            return array();
        }

        $supplier_ids_str = implode(',', array_map('intval', $supplier_ids));

        $query = $this->db->query("SELECT s.supplier_id,
                                          CONCAT(s.firstname, ' ', COALESCE(s.lastname, '')) AS supplier_name,
                                          AVG(se.quality_score) as avg_quality,
                                          AVG(se.delivery_score) as avg_delivery,
                                          AVG(se.price_score) as avg_price,
                                          AVG(se.service_score) as avg_service,
                                          AVG(se.overall_score) as avg_overall,
                                          COUNT(se.evaluation_id) as total_evaluations
                                   FROM " . DB_PREFIX . "supplier s
                                   LEFT JOIN " . DB_PREFIX . "supplier_evaluation se ON (s.supplier_id = se.supplier_id)
                                   WHERE s.supplier_id IN (" . $supplier_ids_str . ")
                                   GROUP BY s.supplier_id
                                   ORDER BY avg_overall DESC");

        return $query->rows;
    }
}
