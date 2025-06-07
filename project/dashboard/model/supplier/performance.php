<?php
/**
 * AYM ERP - Supplier Performance Model
 *
 * @author AYM ERP Development Team
 * @copyright 2024 AYM ERP
 * @license Commercial License
 * @version 1.0.0
 * @link https://aym-erp.com
 */

class ModelSupplierPerformance extends Model {

    public function getSuppliers($data = array()) {
        $sql = "SELECT s.supplier_id, s.name, s.email, s.telephone,
                COALESCE(AVG(spe.overall_score), 0) as overall_score,
                COALESCE(AVG(spe.delivery_score), 0) as delivery_score,
                COALESCE(AVG(spe.quality_score), 0) as quality_score,
                COALESCE(AVG(spe.cost_score), 0) as cost_score,
                COUNT(DISTINCT po.purchase_order_id) as total_orders,
                MAX(spe.evaluation_date) as last_evaluation
                FROM " . DB_PREFIX . "supplier s
                LEFT JOIN " . DB_PREFIX . "supplier_performance_evaluation spe ON (s.supplier_id = spe.supplier_id)
                LEFT JOIN " . DB_PREFIX . "purchase_order po ON (s.supplier_id = po.supplier_id)
                WHERE s.status = '1'";

        if (!empty($data['filter_name'])) {
            $sql .= " AND s.name LIKE '" . $this->db->escape($data['filter_name']) . "%'";
        }

        if (!empty($data['filter_score_min'])) {
            $sql .= " HAVING overall_score >= '" . (float)$data['filter_score_min'] . "'";
        }

        $sql .= " GROUP BY s.supplier_id";

        $sort_data = array(
            's.name',
            'overall_score',
            'delivery_score',
            'quality_score',
            'cost_score',
            'total_orders',
            'last_evaluation'
        );

        if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
            $sql .= " ORDER BY " . $data['sort'];
        } else {
            $sql .= " ORDER BY overall_score";
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

    public function getTotalSuppliers($data = array()) {
        $sql = "SELECT COUNT(DISTINCT s.supplier_id) AS total
                FROM " . DB_PREFIX . "supplier s
                WHERE s.status = '1'";

        if (!empty($data['filter_name'])) {
            $sql .= " AND s.name LIKE '" . $this->db->escape($data['filter_name']) . "%'";
        }

        $query = $this->db->query($sql);

        return $query->row['total'];
    }

    public function getSupplierMetrics($supplier_id) {
        $query = $this->db->query("SELECT
            AVG(overall_score) as avg_overall_score,
            AVG(delivery_score) as avg_delivery_score,
            AVG(quality_score) as avg_quality_score,
            AVG(cost_score) as avg_cost_score,
            AVG(service_score) as avg_service_score,
            COUNT(*) as total_evaluations,
            MAX(evaluation_date) as last_evaluation_date,
            MIN(evaluation_date) as first_evaluation_date
            FROM " . DB_PREFIX . "supplier_performance_evaluation
            WHERE supplier_id = '" . (int)$supplier_id . "'");

        return $query->row;
    }

    public function getDeliveryPerformance($supplier_id) {
        $query = $this->db->query("SELECT
            COUNT(*) as total_deliveries,
            SUM(CASE WHEN delivery_status = 'on_time' THEN 1 ELSE 0 END) as on_time_deliveries,
            SUM(CASE WHEN delivery_status = 'early' THEN 1 ELSE 0 END) as early_deliveries,
            SUM(CASE WHEN delivery_status = 'late' THEN 1 ELSE 0 END) as late_deliveries,
            AVG(DATEDIFF(actual_delivery_date, promised_delivery_date)) as avg_delay_days,
            AVG(delivery_score) as avg_delivery_score
            FROM " . DB_PREFIX . "supplier_delivery_performance
            WHERE supplier_id = '" . (int)$supplier_id . "'
            AND delivery_date >= DATE_SUB(NOW(), INTERVAL 12 MONTH)");

        $result = $query->row;

        if ($result['total_deliveries'] > 0) {
            $result['on_time_percentage'] = ($result['on_time_deliveries'] / $result['total_deliveries']) * 100;
            $result['early_percentage'] = ($result['early_deliveries'] / $result['total_deliveries']) * 100;
            $result['late_percentage'] = ($result['late_deliveries'] / $result['total_deliveries']) * 100;
        } else {
            $result['on_time_percentage'] = 0;
            $result['early_percentage'] = 0;
            $result['late_percentage'] = 0;
        }

        return $result;
    }

    public function getQualityMetrics($supplier_id) {
        $query = $this->db->query("SELECT
            COUNT(*) as total_inspections,
            SUM(CASE WHEN quality_status = 'passed' THEN 1 ELSE 0 END) as passed_inspections,
            SUM(CASE WHEN quality_status = 'failed' THEN 1 ELSE 0 END) as failed_inspections,
            SUM(defect_quantity) as total_defects,
            SUM(received_quantity) as total_received,
            AVG(quality_score) as avg_quality_score
            FROM " . DB_PREFIX . "supplier_quality_inspection
            WHERE supplier_id = '" . (int)$supplier_id . "'
            AND inspection_date >= DATE_SUB(NOW(), INTERVAL 12 MONTH)");

        $result = $query->row;

        if ($result['total_inspections'] > 0) {
            $result['pass_rate'] = ($result['passed_inspections'] / $result['total_inspections']) * 100;
            $result['fail_rate'] = ($result['failed_inspections'] / $result['total_inspections']) * 100;
        } else {
            $result['pass_rate'] = 0;
            $result['fail_rate'] = 0;
        }

        if ($result['total_received'] > 0) {
            $result['defect_rate'] = ($result['total_defects'] / $result['total_received']) * 100;
        } else {
            $result['defect_rate'] = 0;
        }

        return $result;
    }

    public function getCostAnalysis($supplier_id) {
        $query = $this->db->query("SELECT
            COUNT(DISTINCT po.purchase_order_id) as total_orders,
            SUM(po.total) as total_value,
            AVG(po.total) as avg_order_value,
            SUM(CASE WHEN po.total <= budget_amount THEN 1 ELSE 0 END) as within_budget_orders,
            AVG(cost_score) as avg_cost_score
            FROM " . DB_PREFIX . "purchase_order po
            LEFT JOIN " . DB_PREFIX . "supplier_performance_evaluation spe ON (po.supplier_id = spe.supplier_id)
            WHERE po.supplier_id = '" . (int)$supplier_id . "'
            AND po.date_added >= DATE_SUB(NOW(), INTERVAL 12 MONTH)");

        $result = $query->row;

        if ($result['total_orders'] > 0) {
            $result['budget_compliance'] = ($result['within_budget_orders'] / $result['total_orders']) * 100;
        } else {
            $result['budget_compliance'] = 0;
        }

        return $result;
    }

    public function getPerformanceHistory($supplier_id, $months = 12) {
        $query = $this->db->query("SELECT
            DATE_FORMAT(evaluation_date, '%Y-%m') as month,
            AVG(overall_score) as overall_score,
            AVG(delivery_score) as delivery_score,
            AVG(quality_score) as quality_score,
            AVG(cost_score) as cost_score,
            AVG(service_score) as service_score,
            COUNT(*) as evaluation_count
            FROM " . DB_PREFIX . "supplier_performance_evaluation
            WHERE supplier_id = '" . (int)$supplier_id . "'
            AND evaluation_date >= DATE_SUB(NOW(), INTERVAL " . (int)$months . " MONTH)
            GROUP BY DATE_FORMAT(evaluation_date, '%Y-%m')
            ORDER BY month ASC");

        return $query->rows;
    }

    public function getRecentOrders($supplier_id, $limit = 10) {
        $query = $this->db->query("SELECT
            po.purchase_order_id,
            po.order_number,
            po.total,
            po.status,
            po.date_added,
            po.delivery_date,
            COUNT(pop.product_id) as item_count
            FROM " . DB_PREFIX . "purchase_order po
            LEFT JOIN " . DB_PREFIX . "purchase_order_product pop ON (po.purchase_order_id = pop.purchase_order_id)
            WHERE po.supplier_id = '" . (int)$supplier_id . "'
            GROUP BY po.purchase_order_id
            ORDER BY po.date_added DESC
            LIMIT " . (int)$limit);

        return $query->rows;
    }

    public function addEvaluation($data) {
        $overall_score = 0;
        $criteria_count = 0;

        foreach ($data['criteria_scores'] as $criteria_id => $score) {
            $overall_score += (float)$score;
            $criteria_count++;
        }

        if ($criteria_count > 0) {
            $overall_score = $overall_score / $criteria_count;
        }

        $this->db->query("INSERT INTO " . DB_PREFIX . "supplier_performance_evaluation SET
            supplier_id = '" . (int)$data['supplier_id'] . "',
            evaluation_period = '" . $this->db->escape($data['evaluation_period']) . "',
            overall_score = '" . (float)$overall_score . "',
            delivery_score = '" . (float)$data['criteria_scores']['delivery'] . "',
            quality_score = '" . (float)$data['criteria_scores']['quality'] . "',
            cost_score = '" . (float)$data['criteria_scores']['cost'] . "',
            service_score = '" . (float)$data['criteria_scores']['service'] . "',
            comments = '" . $this->db->escape($data['comments']) . "',
            evaluator_id = '" . (int)$this->user->getId() . "',
            evaluation_date = NOW()");

        $evaluation_id = $this->db->getLastId();

        // Add detailed criteria scores
        foreach ($data['criteria_scores'] as $criteria_id => $score) {
            $this->db->query("INSERT INTO " . DB_PREFIX . "supplier_evaluation_criteria SET
                evaluation_id = '" . (int)$evaluation_id . "',
                criteria_name = '" . $this->db->escape($criteria_id) . "',
                score = '" . (float)$score . "',
                weight = '" . (float)($data['criteria_weights'][$criteria_id] ?? 1) . "'");
        }

        return $evaluation_id;
    }

    public function getEvaluationCriteria() {
        return array(
            'delivery' => array(
                'name' => 'Delivery Performance',
                'description' => 'On-time delivery, accuracy, and reliability',
                'weight' => 25
            ),
            'quality' => array(
                'name' => 'Quality Standards',
                'description' => 'Product quality, defect rates, and compliance',
                'weight' => 30
            ),
            'cost' => array(
                'name' => 'Cost Competitiveness',
                'description' => 'Pricing, value for money, and cost stability',
                'weight' => 25
            ),
            'service' => array(
                'name' => 'Customer Service',
                'description' => 'Communication, responsiveness, and support',
                'weight' => 20
            )
        );
    }

    public function getPerformanceOverview() {
        $query = $this->db->query("SELECT
            COUNT(DISTINCT s.supplier_id) as total_suppliers,
            AVG(spe.overall_score) as avg_overall_score,
            COUNT(DISTINCT CASE WHEN spe.overall_score >= 80 THEN s.supplier_id END) as excellent_suppliers,
            COUNT(DISTINCT CASE WHEN spe.overall_score >= 60 AND spe.overall_score < 80 THEN s.supplier_id END) as good_suppliers,
            COUNT(DISTINCT CASE WHEN spe.overall_score < 60 THEN s.supplier_id END) as poor_suppliers
            FROM " . DB_PREFIX . "supplier s
            LEFT JOIN " . DB_PREFIX . "supplier_performance_evaluation spe ON (s.supplier_id = spe.supplier_id)
            WHERE s.status = '1'");

        return $query->row;
    }

    public function getTopSuppliers($limit = 10) {
        $query = $this->db->query("SELECT
            s.supplier_id,
            s.name,
            AVG(spe.overall_score) as overall_score,
            COUNT(spe.evaluation_id) as evaluation_count,
            MAX(spe.evaluation_date) as last_evaluation
            FROM " . DB_PREFIX . "supplier s
            LEFT JOIN " . DB_PREFIX . "supplier_performance_evaluation spe ON (s.supplier_id = spe.supplier_id)
            WHERE s.status = '1'
            GROUP BY s.supplier_id
            HAVING evaluation_count > 0
            ORDER BY overall_score DESC
            LIMIT " . (int)$limit);

        return $query->rows;
    }

    public function getPerformanceTrends() {
        $query = $this->db->query("SELECT
            DATE_FORMAT(evaluation_date, '%Y-%m') as month,
            AVG(overall_score) as avg_score,
            COUNT(DISTINCT supplier_id) as suppliers_evaluated,
            COUNT(*) as total_evaluations
            FROM " . DB_PREFIX . "supplier_performance_evaluation
            WHERE evaluation_date >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
            GROUP BY DATE_FORMAT(evaluation_date, '%Y-%m')
            ORDER BY month ASC");

        return $query->rows;
    }

    public function getPerformanceAlerts() {
        $alerts = array();

        // Low performing suppliers
        $query = $this->db->query("SELECT
            s.supplier_id,
            s.name,
            AVG(spe.overall_score) as avg_score
            FROM " . DB_PREFIX . "supplier s
            LEFT JOIN " . DB_PREFIX . "supplier_performance_evaluation spe ON (s.supplier_id = spe.supplier_id)
            WHERE s.status = '1'
            AND spe.evaluation_date >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
            GROUP BY s.supplier_id
            HAVING avg_score < 60
            ORDER BY avg_score ASC");

        foreach ($query->rows as $row) {
            $alerts[] = array(
                'type' => 'warning',
                'message' => 'Low performance: ' . $row['name'] . ' (Score: ' . number_format($row['avg_score'], 1) . ')',
                'supplier_id' => $row['supplier_id']
            );
        }

        // Suppliers without recent evaluations
        $query = $this->db->query("SELECT
            s.supplier_id,
            s.name,
            MAX(spe.evaluation_date) as last_evaluation
            FROM " . DB_PREFIX . "supplier s
            LEFT JOIN " . DB_PREFIX . "supplier_performance_evaluation spe ON (s.supplier_id = spe.supplier_id)
            WHERE s.status = '1'
            GROUP BY s.supplier_id
            HAVING last_evaluation IS NULL OR last_evaluation < DATE_SUB(NOW(), INTERVAL 3 MONTH)
            ORDER BY last_evaluation ASC");

        foreach ($query->rows as $row) {
            $alerts[] = array(
                'type' => 'info',
                'message' => 'Evaluation needed: ' . $row['name'] . ' (Last: ' . ($row['last_evaluation'] ? $row['last_evaluation'] : 'Never') . ')',
                'supplier_id' => $row['supplier_id']
            );
        }

        return $alerts;
    }

    public function getPerformanceReport($data = array()) {
        $sql = "SELECT
            s.supplier_id,
            s.name as supplier_name,
            spe.evaluation_date,
            spe.overall_score,
            spe.delivery_score,
            spe.quality_score,
            spe.cost_score,
            spe.service_score,
            spe.comments,
            u.username as evaluator
            FROM " . DB_PREFIX . "supplier s
            LEFT JOIN " . DB_PREFIX . "supplier_performance_evaluation spe ON (s.supplier_id = spe.supplier_id)
            LEFT JOIN " . DB_PREFIX . "user u ON (spe.evaluator_id = u.user_id)
            WHERE s.status = '1'";

        if (!empty($data['filter_supplier_id'])) {
            $sql .= " AND s.supplier_id = '" . (int)$data['filter_supplier_id'] . "'";
        }

        if (!empty($data['filter_date_start'])) {
            $sql .= " AND DATE(spe.evaluation_date) >= DATE('" . $this->db->escape($data['filter_date_start']) . "')";
        }

        if (!empty($data['filter_date_end'])) {
            $sql .= " AND DATE(spe.evaluation_date) <= DATE('" . $this->db->escape($data['filter_date_end']) . "')";
        }

        if (!empty($data['filter_metric'])) {
            switch ($data['filter_metric']) {
                case 'excellent':
                    $sql .= " AND spe.overall_score >= 80";
                    break;
                case 'good':
                    $sql .= " AND spe.overall_score >= 60 AND spe.overall_score < 80";
                    break;
                case 'poor':
                    $sql .= " AND spe.overall_score < 60";
                    break;
            }
        }

        $sql .= " ORDER BY spe.evaluation_date DESC";

        $query = $this->db->query($sql);

        return $query->rows;
    }
}
