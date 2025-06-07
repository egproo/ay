<?php
/**
 * نظام أيم ERP: نظام إدارة موارد الشركات المتكامل للتجارة والتوزيع
 * مع دعم الجرد المستمر والمتوسط المرجح للتكلفة والفروع المتعددة
 *
 * Model: Dashboard Goals (متابعة الأهداف)
 */
class ModelDashboardGoals extends Model {

    /**
     * Add new goal
     *
     * @param array $data
     * @return int
     */
    public function addGoal($data) {
        $this->db->query("INSERT INTO " . DB_PREFIX . "dashboard_goals SET
            goal_title = '" . $this->db->escape($data['goal_title']) . "',
            goal_description = '" . $this->db->escape($data['goal_description']) . "',
            goal_type = '" . $this->db->escape($data['goal_type']) . "',
            target_value = '" . (float)$data['target_value'] . "',
            current_value = '" . (float)$data['current_value'] . "',
            start_date = '" . $this->db->escape($data['start_date']) . "',
            end_date = '" . $this->db->escape($data['end_date']) . "',
            assigned_to = '" . (int)$data['assigned_to'] . "',
            department_id = '" . (int)$data['department_id'] . "',
            priority = '" . $this->db->escape($data['priority']) . "',
            status = '" . $this->db->escape($data['status']) . "',
            created_by = '" . (int)$this->user->getId() . "',
            created_at = NOW()");

        $goal_id = $this->db->getLastId();

        // Add initial progress entry
        $this->addGoalProgress($goal_id, (float)$data['current_value'], 'هدف جديد تم إنشاؤه');

        return $goal_id;
    }

    /**
     * Edit existing goal
     *
     * @param int $goal_id
     * @param array $data
     * @return void
     */
    public function editGoal($goal_id, $data) {
        $this->db->query("UPDATE " . DB_PREFIX . "dashboard_goals SET
            goal_title = '" . $this->db->escape($data['goal_title']) . "',
            goal_description = '" . $this->db->escape($data['goal_description']) . "',
            goal_type = '" . $this->db->escape($data['goal_type']) . "',
            target_value = '" . (float)$data['target_value'] . "',
            current_value = '" . (float)$data['current_value'] . "',
            start_date = '" . $this->db->escape($data['start_date']) . "',
            end_date = '" . $this->db->escape($data['end_date']) . "',
            assigned_to = '" . (int)$data['assigned_to'] . "',
            department_id = '" . (int)$data['department_id'] . "',
            priority = '" . $this->db->escape($data['priority']) . "',
            status = '" . $this->db->escape($data['status']) . "',
            updated_at = NOW()
            WHERE goal_id = '" . (int)$goal_id . "'");
    }

    /**
     * Delete goal
     *
     * @param int $goal_id
     * @return void
     */
    public function deleteGoal($goal_id) {
        $this->db->query("DELETE FROM " . DB_PREFIX . "dashboard_goals WHERE goal_id = '" . (int)$goal_id . "'");
        $this->db->query("DELETE FROM " . DB_PREFIX . "dashboard_goal_progress WHERE goal_id = '" . (int)$goal_id . "'");
    }

    /**
     * Get goal by ID
     *
     * @param int $goal_id
     * @return array
     */
    public function getGoal($goal_id) {
        $query = $this->db->query("SELECT g.*,
            u1.firstname as assigned_firstname, u1.lastname as assigned_lastname,
            u2.firstname as created_firstname, u2.lastname as created_lastname,
            ug.name as department_name
            FROM " . DB_PREFIX . "dashboard_goals g
            LEFT JOIN " . DB_PREFIX . "user u1 ON (g.assigned_to = u1.user_id)
            LEFT JOIN " . DB_PREFIX . "user u2 ON (g.created_by = u2.user_id)
            LEFT JOIN " . DB_PREFIX . "user_group ug ON (g.department_id = ug.user_group_id)
            WHERE g.goal_id = '" . (int)$goal_id . "'");

        if ($query->num_rows) {
            $goal = $query->row;

            // Calculate progress percentage
            $goal['progress_percentage'] = $goal['target_value'] > 0 ?
                round(($goal['current_value'] / $goal['target_value']) * 100, 2) : 0;

            // Calculate days remaining
            $goal['days_remaining'] = max(0, ceil((strtotime($goal['end_date']) - time()) / (24 * 60 * 60)));

            // Determine status based on progress and dates
            $goal['calculated_status'] = $this->calculateGoalStatus($goal);

            return $goal;
        }

        return array();
    }

    /**
     * Get goals list with filters
     *
     * @param array $data
     * @return array
     */
    public function getGoals($data = array()) {
        $sql = "SELECT g.*,
            u1.firstname as assigned_firstname, u1.lastname as assigned_lastname,
            u2.firstname as created_firstname, u2.lastname as created_lastname,
            ug.name as department_name
            FROM " . DB_PREFIX . "dashboard_goals g
            LEFT JOIN " . DB_PREFIX . "user u1 ON (g.assigned_to = u1.user_id)
            LEFT JOIN " . DB_PREFIX . "user u2 ON (g.created_by = u2.user_id)
            LEFT JOIN " . DB_PREFIX . "user_group ug ON (g.department_id = ug.user_group_id)
            WHERE 1=1";

        // Apply filters
        if (!empty($data['filter_period'])) {
            switch ($data['filter_period']) {
                case 'current_month':
                    $sql .= " AND g.start_date <= LAST_DAY(CURDATE()) AND g.end_date >= DATE_FORMAT(CURDATE(), '%Y-%m-01')";
                    break;
                case 'current_quarter':
                    $sql .= " AND g.start_date <= LAST_DAY(CURDATE() + INTERVAL (3 - MONTH(CURDATE()) % 3) MONTH)
                             AND g.end_date >= DATE_FORMAT(CURDATE() - INTERVAL (MONTH(CURDATE()) - 1) % 3 MONTH, '%Y-%m-01')";
                    break;
                case 'current_year':
                    $sql .= " AND g.start_date <= CONCAT(YEAR(CURDATE()), '-12-31') AND g.end_date >= CONCAT(YEAR(CURDATE()), '-01-01')";
                    break;
                case 'overdue':
                    $sql .= " AND g.end_date < CURDATE() AND g.status != 'completed'";
                    break;
            }
        }

        if (!empty($data['filter_department'])) {
            $sql .= " AND g.department_id = '" . (int)$data['filter_department'] . "'";
        }

        if (!empty($data['filter_status'])) {
            $sql .= " AND g.status = '" . $this->db->escape($data['filter_status']) . "'";
        }

        // Add sorting
        $sql .= " ORDER BY g.priority DESC, g.end_date ASC";

        // Add limit
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

        $goals = array();
        foreach ($query->rows as $goal) {
            // Calculate progress percentage
            $goal['progress_percentage'] = $goal['target_value'] > 0 ?
                round(($goal['current_value'] / $goal['target_value']) * 100, 2) : 0;

            // Calculate days remaining
            $goal['days_remaining'] = max(0, ceil((strtotime($goal['end_date']) - time()) / (24 * 60 * 60)));

            // Determine status based on progress and dates
            $goal['calculated_status'] = $this->calculateGoalStatus($goal);

            $goals[] = $goal;
        }

        return $goals;
    }

    /**
     * Get goals summary statistics
     *
     * @param array $data
     * @return array
     */
    public function getGoalsSummary($data = array()) {
        $sql = "SELECT
            COUNT(*) as total_goals,
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_goals,
            SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_goals,
            SUM(CASE WHEN status = 'paused' THEN 1 ELSE 0 END) as paused_goals,
            SUM(CASE WHEN end_date < CURDATE() AND status != 'completed' THEN 1 ELSE 0 END) as overdue_goals,
            AVG(CASE WHEN target_value > 0 THEN (current_value / target_value) * 100 ELSE 0 END) as avg_progress
            FROM " . DB_PREFIX . "dashboard_goals
            WHERE 1=1";

        // Apply same filters as getGoals
        if (!empty($data['filter_period'])) {
            switch ($data['filter_period']) {
                case 'current_month':
                    $sql .= " AND start_date <= LAST_DAY(CURDATE()) AND end_date >= DATE_FORMAT(CURDATE(), '%Y-%m-01')";
                    break;
                case 'current_quarter':
                    $sql .= " AND start_date <= LAST_DAY(CURDATE() + INTERVAL (3 - MONTH(CURDATE()) % 3) MONTH)
                             AND end_date >= DATE_FORMAT(CURDATE() - INTERVAL (MONTH(CURDATE()) - 1) % 3 MONTH, '%Y-%m-01')";
                    break;
                case 'current_year':
                    $sql .= " AND start_date <= CONCAT(YEAR(CURDATE()), '-12-31') AND end_date >= CONCAT(YEAR(CURDATE()), '-01-01')";
                    break;
            }
        }

        if (!empty($data['filter_department'])) {
            $sql .= " AND department_id = '" . (int)$data['filter_department'] . "'";
        }

        $query = $this->db->query($sql);

        if ($query->num_rows) {
            $summary = $query->row;
            $summary['avg_progress'] = round($summary['avg_progress'], 2);
            $summary['completion_rate'] = $summary['total_goals'] > 0 ?
                round(($summary['completed_goals'] / $summary['total_goals']) * 100, 2) : 0;

            return $summary;
        }

        return array(
            'total_goals' => 0,
            'completed_goals' => 0,
            'active_goals' => 0,
            'paused_goals' => 0,
            'overdue_goals' => 0,
            'avg_progress' => 0,
            'completion_rate' => 0
        );
    }

    /**
     * Update goal progress
     *
     * @param int $goal_id
     * @param float $current_value
     * @param string $notes
     * @return void
     */
    public function updateGoalProgress($goal_id, $current_value, $notes = '') {
        // Update current value in goals table
        $this->db->query("UPDATE " . DB_PREFIX . "dashboard_goals SET
            current_value = '" . (float)$current_value . "',
            updated_at = NOW()
            WHERE goal_id = '" . (int)$goal_id . "'");

        // Add progress entry
        $this->addGoalProgress($goal_id, $current_value, $notes);

        // Check if goal is completed
        $goal = $this->getGoal($goal_id);
        if ($goal && $goal['current_value'] >= $goal['target_value'] && $goal['status'] != 'completed') {
            $this->db->query("UPDATE " . DB_PREFIX . "dashboard_goals SET
                status = 'completed',
                completed_at = NOW()
                WHERE goal_id = '" . (int)$goal_id . "'");
        }
    }

    /**
     * Add goal progress entry
     *
     * @param int $goal_id
     * @param float $value
     * @param string $notes
     * @return void
     */
    public function addGoalProgress($goal_id, $value, $notes = '') {
        $this->db->query("INSERT INTO " . DB_PREFIX . "dashboard_goal_progress SET
            goal_id = '" . (int)$goal_id . "',
            progress_value = '" . (float)$value . "',
            notes = '" . $this->db->escape($notes) . "',
            recorded_by = '" . (int)$this->user->getId() . "',
            recorded_at = NOW()");
    }

    /**
     * Get goal progress history
     *
     * @param int $goal_id
     * @return array
     */
    public function getGoalProgress($goal_id) {
        $query = $this->db->query("SELECT gp.*,
            u.firstname, u.lastname
            FROM " . DB_PREFIX . "dashboard_goal_progress gp
            LEFT JOIN " . DB_PREFIX . "user u ON (gp.recorded_by = u.user_id)
            WHERE gp.goal_id = '" . (int)$goal_id . "'
            ORDER BY gp.recorded_at DESC");

        return $query->rows;
    }

    /**
     * Calculate goal status based on progress and dates
     *
     * @param array $goal
     * @return string
     */
    private function calculateGoalStatus($goal) {
        $now = time();
        $start_time = strtotime($goal['start_date']);
        $end_time = strtotime($goal['end_date']);

        // If manually set to completed
        if ($goal['status'] == 'completed') {
            return 'completed';
        }

        // If manually paused
        if ($goal['status'] == 'paused') {
            return 'paused';
        }

        // If overdue
        if ($now > $end_time) {
            return 'overdue';
        }

        // If not started yet
        if ($now < $start_time) {
            return 'not_started';
        }

        // Calculate expected progress based on time
        $total_duration = $end_time - $start_time;
        $elapsed_duration = $now - $start_time;
        $expected_progress = ($elapsed_duration / $total_duration) * 100;

        $actual_progress = $goal['target_value'] > 0 ?
            ($goal['current_value'] / $goal['target_value']) * 100 : 0;

        // Determine status based on progress vs expected
        if ($actual_progress >= 100) {
            return 'completed';
        } elseif ($actual_progress >= $expected_progress * 0.8) {
            return 'on_track';
        } elseif ($actual_progress >= $expected_progress * 0.5) {
            return 'behind';
        } else {
            return 'at_risk';
        }
    }

    /**
     * Get total goals count
     *
     * @param array $data
     * @return int
     */
    public function getTotalGoals($data = array()) {
        $sql = "SELECT COUNT(*) as total FROM " . DB_PREFIX . "dashboard_goals WHERE 1=1";

        // Apply same filters as getGoals
        if (!empty($data['filter_period'])) {
            switch ($data['filter_period']) {
                case 'current_month':
                    $sql .= " AND start_date <= LAST_DAY(CURDATE()) AND end_date >= DATE_FORMAT(CURDATE(), '%Y-%m-01')";
                    break;
                case 'current_quarter':
                    $sql .= " AND start_date <= LAST_DAY(CURDATE() + INTERVAL (3 - MONTH(CURDATE()) % 3) MONTH)
                             AND end_date >= DATE_FORMAT(CURDATE() - INTERVAL (MONTH(CURDATE()) - 1) % 3 MONTH, '%Y-%m-01')";
                    break;
                case 'current_year':
                    $sql .= " AND start_date <= CONCAT(YEAR(CURDATE()), '-12-31') AND end_date >= CONCAT(YEAR(CURDATE()), '-01-01')";
                    break;
            }
        }

        if (!empty($data['filter_department'])) {
            $sql .= " AND department_id = '" . (int)$data['filter_department'] . "'";
        }

        if (!empty($data['filter_status'])) {
            $sql .= " AND status = '" . $this->db->escape($data['filter_status']) . "'";
        }

        $query = $this->db->query($sql);

        return $query->row['total'];
    }
}
