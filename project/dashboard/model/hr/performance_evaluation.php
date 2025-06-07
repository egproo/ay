<?php
/**
 * نموذج نظام تقييم الأداء المتقدم
 * 
 * يوفر إدارة شاملة لتقييم الأداء مع:
 * - نماذج التقييم المرنة
 * - دورات التقييم المتعددة
 * - التقييم الذاتي وتقييم المدير
 * - ربط النتائج بالمكافآت والترقيات
 * - تقارير الأداء المتقدمة
 * 
 * @author ERP Team
 * @version 1.0
 * @since 2024
 */

class ModelHrPerformanceEvaluation extends Model {
    
    /**
     * إنشاء نموذج تقييم جديد
     */
    public function addEvaluationTemplate($data) {
        $this->db->query("
            INSERT INTO cod_evaluation_template SET 
            template_name = '" . $this->db->escape($data['template_name']) . "',
            description = '" . $this->db->escape($data['description']) . "',
            evaluation_type = '" . $this->db->escape($data['evaluation_type']) . "',
            scoring_method = '" . $this->db->escape($data['scoring_method']) . "',
            max_score = '" . (int)$data['max_score'] . "',
            status = 'active',
            created_by = '" . (int)$this->user->getId() . "',
            date_created = NOW()
        ");
        
        $template_id = $this->db->getLastId();
        
        // إضافة معايير التقييم
        if (isset($data['criteria']) && is_array($data['criteria'])) {
            foreach ($data['criteria'] as $criterion) {
                $this->addEvaluationCriterion($template_id, $criterion);
            }
        }
        
        return $template_id;
    }
    
    /**
     * إضافة معيار تقييم
     */
    public function addEvaluationCriterion($template_id, $data) {
        $this->db->query("
            INSERT INTO cod_evaluation_criterion SET 
            template_id = '" . (int)$template_id . "',
            criterion_name = '" . $this->db->escape($data['criterion_name']) . "',
            description = '" . $this->db->escape($data['description']) . "',
            weight = '" . (float)$data['weight'] . "',
            max_score = '" . (int)$data['max_score'] . "',
            criterion_type = '" . $this->db->escape($data['criterion_type']) . "',
            sort_order = '" . (int)$data['sort_order'] . "'
        ");
        
        return $this->db->getLastId();
    }
    
    /**
     * إنشاء دورة تقييم جديدة
     */
    public function addEvaluationCycle($data) {
        $this->db->query("
            INSERT INTO cod_evaluation_cycle SET 
            cycle_name = '" . $this->db->escape($data['cycle_name']) . "',
            template_id = '" . (int)$data['template_id'] . "',
            evaluation_period = '" . $this->db->escape($data['evaluation_period']) . "',
            start_date = '" . $this->db->escape($data['start_date']) . "',
            end_date = '" . $this->db->escape($data['end_date']) . "',
            self_evaluation_deadline = '" . $this->db->escape($data['self_evaluation_deadline']) . "',
            manager_evaluation_deadline = '" . $this->db->escape($data['manager_evaluation_deadline']) . "',
            status = 'active',
            created_by = '" . (int)$this->user->getId() . "',
            date_created = NOW()
        ");
        
        $cycle_id = $this->db->getLastId();
        
        // إنشاء تقييمات للموظفين المحددين
        if (isset($data['employees']) && is_array($data['employees'])) {
            foreach ($data['employees'] as $employee_id) {
                $this->createEmployeeEvaluation($cycle_id, $employee_id);
            }
        }
        
        return $cycle_id;
    }
    
    /**
     * إنشاء تقييم موظف
     */
    private function createEmployeeEvaluation($cycle_id, $employee_id) {
        $this->db->query("
            INSERT INTO cod_employee_evaluation SET 
            cycle_id = '" . (int)$cycle_id . "',
            employee_id = '" . (int)$employee_id . "',
            self_evaluation_status = 'pending',
            manager_evaluation_status = 'pending',
            overall_status = 'pending',
            date_created = NOW()
        ");
        
        return $this->db->getLastId();
    }
    
    /**
     * حفظ التقييم الذاتي
     */
    public function saveSelfEvaluation($evaluation_id, $data) {
        // التحقق من صلاحية التقييم
        $evaluation = $this->getEmployeeEvaluation($evaluation_id);
        if (!$evaluation || $evaluation['self_evaluation_status'] == 'completed') {
            throw new Exception('لا يمكن تعديل هذا التقييم');
        }
        
        // حفظ درجات المعايير
        foreach ($data['criteria_scores'] as $criterion_id => $score) {
            $this->db->query("
                INSERT INTO cod_evaluation_score SET 
                evaluation_id = '" . (int)$evaluation_id . "',
                criterion_id = '" . (int)$criterion_id . "',
                score_type = 'self',
                score = '" . (float)$score['score'] . "',
                comments = '" . $this->db->escape($score['comments']) . "',
                date_scored = NOW()
                ON DUPLICATE KEY UPDATE 
                score = '" . (float)$score['score'] . "',
                comments = '" . $this->db->escape($score['comments']) . "',
                date_scored = NOW()
            ");
        }
        
        // تحديث حالة التقييم الذاتي
        $this->db->query("
            UPDATE cod_employee_evaluation SET 
            self_evaluation_status = 'completed',
            self_evaluation_comments = '" . $this->db->escape($data['overall_comments']) . "',
            self_evaluation_date = NOW()
            WHERE evaluation_id = '" . (int)$evaluation_id . "'
        ");
        
        // حساب النتيجة الإجمالية للتقييم الذاتي
        $this->calculateSelfScore($evaluation_id);
        
        return true;
    }
    
    /**
     * حفظ تقييم المدير
     */
    public function saveManagerEvaluation($evaluation_id, $data) {
        // التحقق من صلاحية التقييم
        $evaluation = $this->getEmployeeEvaluation($evaluation_id);
        if (!$evaluation || $evaluation['manager_evaluation_status'] == 'completed') {
            throw new Exception('لا يمكن تعديل هذا التقييم');
        }
        
        // حفظ درجات المعايير
        foreach ($data['criteria_scores'] as $criterion_id => $score) {
            $this->db->query("
                INSERT INTO cod_evaluation_score SET 
                evaluation_id = '" . (int)$evaluation_id . "',
                criterion_id = '" . (int)$criterion_id . "',
                score_type = 'manager',
                score = '" . (float)$score['score'] . "',
                comments = '" . $this->db->escape($score['comments']) . "',
                scored_by = '" . (int)$this->user->getId() . "',
                date_scored = NOW()
                ON DUPLICATE KEY UPDATE 
                score = '" . (float)$score['score'] . "',
                comments = '" . $this->db->escape($score['comments']) . "',
                scored_by = '" . (int)$this->user->getId() . "',
                date_scored = NOW()
            ");
        }
        
        // تحديث حالة تقييم المدير
        $this->db->query("
            UPDATE cod_employee_evaluation SET 
            manager_evaluation_status = 'completed',
            manager_evaluation_comments = '" . $this->db->escape($data['overall_comments']) . "',
            manager_evaluation_date = NOW(),
            evaluated_by = '" . (int)$this->user->getId() . "'
            WHERE evaluation_id = '" . (int)$evaluation_id . "'
        ");
        
        // حساب النتيجة الإجمالية لتقييم المدير
        $this->calculateManagerScore($evaluation_id);
        
        // تحديث الحالة الإجمالية
        $this->updateOverallStatus($evaluation_id);
        
        return true;
    }
    
    /**
     * حساب النتيجة الإجمالية للتقييم الذاتي
     */
    private function calculateSelfScore($evaluation_id) {
        $query = $this->db->query("
            SELECT 
                SUM(es.score * ec.weight / 100) as weighted_score,
                SUM(ec.max_score * ec.weight / 100) as max_weighted_score
            FROM cod_evaluation_score es
            LEFT JOIN cod_evaluation_criterion ec ON (es.criterion_id = ec.criterion_id)
            WHERE es.evaluation_id = '" . (int)$evaluation_id . "'
            AND es.score_type = 'self'
        ");
        
        if ($query->num_rows) {
            $result = $query->row;
            $percentage = ($result['max_weighted_score'] > 0) ? 
                ($result['weighted_score'] / $result['max_weighted_score']) * 100 : 0;
            
            $this->db->query("
                UPDATE cod_employee_evaluation SET 
                self_total_score = '" . (float)$result['weighted_score'] . "',
                self_percentage = '" . (float)$percentage . "'
                WHERE evaluation_id = '" . (int)$evaluation_id . "'
            ");
        }
    }
    
    /**
     * حساب النتيجة الإجمالية لتقييم المدير
     */
    private function calculateManagerScore($evaluation_id) {
        $query = $this->db->query("
            SELECT 
                SUM(es.score * ec.weight / 100) as weighted_score,
                SUM(ec.max_score * ec.weight / 100) as max_weighted_score
            FROM cod_evaluation_score es
            LEFT JOIN cod_evaluation_criterion ec ON (es.criterion_id = ec.criterion_id)
            WHERE es.evaluation_id = '" . (int)$evaluation_id . "'
            AND es.score_type = 'manager'
        ");
        
        if ($query->num_rows) {
            $result = $query->row;
            $percentage = ($result['max_weighted_score'] > 0) ? 
                ($result['weighted_score'] / $result['max_weighted_score']) * 100 : 0;
            
            $this->db->query("
                UPDATE cod_employee_evaluation SET 
                manager_total_score = '" . (float)$result['weighted_score'] . "',
                manager_percentage = '" . (float)$percentage . "',
                final_score = '" . (float)$result['weighted_score'] . "',
                final_percentage = '" . (float)$percentage . "'
                WHERE evaluation_id = '" . (int)$evaluation_id . "'
            ");
        }
    }
    
    /**
     * تحديث الحالة الإجمالية للتقييم
     */
    private function updateOverallStatus($evaluation_id) {
        $evaluation = $this->getEmployeeEvaluation($evaluation_id);
        
        if ($evaluation['self_evaluation_status'] == 'completed' && 
            $evaluation['manager_evaluation_status'] == 'completed') {
            
            $this->db->query("
                UPDATE cod_employee_evaluation SET 
                overall_status = 'completed',
                completion_date = NOW()
                WHERE evaluation_id = '" . (int)$evaluation_id . "'
            ");
            
            // تحديد تصنيف الأداء
            $this->assignPerformanceRating($evaluation_id);
        }
    }
    
    /**
     * تحديد تصنيف الأداء
     */
    private function assignPerformanceRating($evaluation_id) {
        $evaluation = $this->getEmployeeEvaluation($evaluation_id);
        $percentage = $evaluation['final_percentage'];
        
        $rating = '';
        $rating_description = '';
        
        if ($percentage >= 90) {
            $rating = 'excellent';
            $rating_description = 'ممتاز';
        } elseif ($percentage >= 80) {
            $rating = 'very_good';
            $rating_description = 'جيد جداً';
        } elseif ($percentage >= 70) {
            $rating = 'good';
            $rating_description = 'جيد';
        } elseif ($percentage >= 60) {
            $rating = 'satisfactory';
            $rating_description = 'مقبول';
        } else {
            $rating = 'needs_improvement';
            $rating_description = 'يحتاج تحسين';
        }
        
        $this->db->query("
            UPDATE cod_employee_evaluation SET 
            performance_rating = '" . $this->db->escape($rating) . "',
            rating_description = '" . $this->db->escape($rating_description) . "'
            WHERE evaluation_id = '" . (int)$evaluation_id . "'
        ");
    }
    
    /**
     * ربط التقييم بمكافأة
     */
    public function linkPerformanceBonus($evaluation_id, $bonus_amount, $bonus_reason) {
        $evaluation = $this->getEmployeeEvaluation($evaluation_id);
        
        if (!$evaluation || $evaluation['overall_status'] != 'completed') {
            throw new Exception('يجب اكتمال التقييم أولاً');
        }
        
        $this->db->query("
            INSERT INTO cod_performance_bonus SET 
            employee_id = '" . (int)$evaluation['employee_id'] . "',
            evaluation_id = '" . (int)$evaluation_id . "',
            bonus_amount = '" . (float)$bonus_amount . "',
            bonus_reason = '" . $this->db->escape($bonus_reason) . "',
            bonus_date = NOW(),
            status = 'approved',
            created_by = '" . (int)$this->user->getId() . "',
            date_created = NOW()
        ");
        
        return $this->db->getLastId();
    }
    
    /**
     * الحصول على تقييم الموظف
     */
    public function getEmployeeEvaluation($evaluation_id) {
        $query = $this->db->query("
            SELECT ee.*, 
                ec.cycle_name, ec.evaluation_period,
                et.template_name, et.scoring_method, et.max_score,
                ep.job_title,
                CONCAT(u.firstname, ' ', u.lastname) as employee_name,
                CONCAT(u2.firstname, ' ', u2.lastname) as evaluated_by_name
            FROM cod_employee_evaluation ee
            LEFT JOIN cod_evaluation_cycle ec ON (ee.cycle_id = ec.cycle_id)
            LEFT JOIN cod_evaluation_template et ON (ec.template_id = et.template_id)
            LEFT JOIN cod_employee_profile ep ON (ee.employee_id = ep.employee_id)
            LEFT JOIN cod_user u ON (ep.user_id = u.user_id)
            LEFT JOIN cod_user u2 ON (ee.evaluated_by = u2.user_id)
            WHERE ee.evaluation_id = '" . (int)$evaluation_id . "'
        ");
        
        return $query->num_rows ? $query->row : false;
    }
    
    /**
     * الحصول على معايير التقييم مع الدرجات
     */
    public function getEvaluationCriteriaWithScores($evaluation_id) {
        $query = $this->db->query("
            SELECT ec.*, 
                es_self.score as self_score,
                es_self.comments as self_comments,
                es_manager.score as manager_score,
                es_manager.comments as manager_comments
            FROM cod_evaluation_criterion ec
            LEFT JOIN cod_employee_evaluation ee ON (1=1)
            LEFT JOIN cod_evaluation_cycle cycle ON (ee.cycle_id = cycle.cycle_id)
            LEFT JOIN cod_evaluation_score es_self ON (ec.criterion_id = es_self.criterion_id AND es_self.evaluation_id = ee.evaluation_id AND es_self.score_type = 'self')
            LEFT JOIN cod_evaluation_score es_manager ON (ec.criterion_id = es_manager.criterion_id AND es_manager.evaluation_id = ee.evaluation_id AND es_manager.score_type = 'manager')
            WHERE ee.evaluation_id = '" . (int)$evaluation_id . "'
            AND ec.template_id = cycle.template_id
            ORDER BY ec.sort_order
        ");
        
        return $query->rows;
    }
    
    /**
     * الحصول على قائمة التقييمات
     */
    public function getEvaluations($filter_data = []) {
        $sql = "SELECT ee.*, 
                ec.cycle_name, ec.evaluation_period,
                et.template_name,
                ep.job_title,
                CONCAT(u.firstname, ' ', u.lastname) as employee_name,
                CONCAT(u2.firstname, ' ', u2.lastname) as evaluated_by_name
                FROM cod_employee_evaluation ee
                LEFT JOIN cod_evaluation_cycle ec ON (ee.cycle_id = ec.cycle_id)
                LEFT JOIN cod_evaluation_template et ON (ec.template_id = et.template_id)
                LEFT JOIN cod_employee_profile ep ON (ee.employee_id = ep.employee_id)
                LEFT JOIN cod_user u ON (ep.user_id = u.user_id)
                LEFT JOIN cod_user u2 ON (ee.evaluated_by = u2.user_id)
                WHERE 1";
        
        if (!empty($filter_data['filter_employee'])) {
            $sql .= " AND ee.employee_id = '" . (int)$filter_data['filter_employee'] . "'";
        }
        
        if (!empty($filter_data['filter_cycle'])) {
            $sql .= " AND ee.cycle_id = '" . (int)$filter_data['filter_cycle'] . "'";
        }
        
        if (!empty($filter_data['filter_status'])) {
            $sql .= " AND ee.overall_status = '" . $this->db->escape($filter_data['filter_status']) . "'";
        }
        
        if (!empty($filter_data['filter_rating'])) {
            $sql .= " AND ee.performance_rating = '" . $this->db->escape($filter_data['filter_rating']) . "'";
        }
        
        $sql .= " ORDER BY ee.date_created DESC";
        
        if (isset($filter_data['start']) || isset($filter_data['limit'])) {
            if ($filter_data['start'] < 0) {
                $filter_data['start'] = 0;
            }
            
            if ($filter_data['limit'] < 1) {
                $filter_data['limit'] = 20;
            }
            
            $sql .= " LIMIT " . (int)$filter_data['start'] . "," . (int)$filter_data['limit'];
        }
        
        $query = $this->db->query($sql);
        
        return $query->rows;
    }
}
