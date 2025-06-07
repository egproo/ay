<?php
/**
 * AYM ERP - Advanced Project Management Model
 *
 * Professional project management system with comprehensive project lifecycle management
 * Features:
 * - Complete project planning and execution
 * - Advanced task management with dependencies
 * - Resource allocation and capacity planning
 * - Time tracking and billing integration
 * - Gantt charts and project visualization
 * - Team collaboration and communication
 * - Budget management and cost tracking
 * - Risk management and issue tracking
 *
 * @author AYM ERP Development Team
 * @copyright 2024 AYM ERP
 * @license Commercial License
 * @version 1.0.0
 * @link https://aym-erp.com
 */

class ModelProjectProject extends Model {

    public function addProject($data) {
        $this->db->query("INSERT INTO " . DB_PREFIX . "project SET
            name = '" . $this->db->escape($data['name']) . "',
            description = '" . $this->db->escape($data['description']) . "',
            client_id = '" . (int)($data['client_id'] ?? 0) . "',
            project_manager_id = '" . (int)$data['project_manager_id'] . "',
            start_date = '" . $this->db->escape($data['start_date']) . "',
            end_date = '" . $this->db->escape($data['end_date']) . "',
            budget = '" . (float)($data['budget'] ?? 0) . "',
            currency = '" . $this->db->escape($data['currency'] ?? 'EGP') . "',
            priority = '" . $this->db->escape($data['priority'] ?? 'medium') . "',
            status = '" . $this->db->escape($data['status'] ?? 'planning') . "',
            progress = 0,
            billing_type = '" . $this->db->escape($data['billing_type'] ?? 'fixed') . "',
            hourly_rate = '" . (float)($data['hourly_rate'] ?? 0) . "',
            is_billable = '" . (int)($data['is_billable'] ?? 1) . "',
            is_public = '" . (int)($data['is_public'] ?? 0) . "',
            created_by = '" . (int)$this->user->getId() . "',
            date_added = NOW()");

        $project_id = $this->db->getLastId();

        // Add project custom fields if provided
        if (isset($data['custom_fields']) && is_array($data['custom_fields'])) {
            $this->addProjectCustomFields($project_id, $data['custom_fields']);
        }

        // Add project tags if provided
        if (isset($data['tags']) && is_array($data['tags'])) {
            $this->addProjectTags($project_id, $data['tags']);
        }

        return $project_id;
    }

    public function editProject($project_id, $data) {
        $this->db->query("UPDATE " . DB_PREFIX . "project SET
            name = '" . $this->db->escape($data['name']) . "',
            description = '" . $this->db->escape($data['description']) . "',
            client_id = '" . (int)($data['client_id'] ?? 0) . "',
            project_manager_id = '" . (int)$data['project_manager_id'] . "',
            start_date = '" . $this->db->escape($data['start_date']) . "',
            end_date = '" . $this->db->escape($data['end_date']) . "',
            budget = '" . (float)($data['budget'] ?? 0) . "',
            currency = '" . $this->db->escape($data['currency'] ?? 'EGP') . "',
            priority = '" . $this->db->escape($data['priority'] ?? 'medium') . "',
            status = '" . $this->db->escape($data['status'] ?? 'planning') . "',
            billing_type = '" . $this->db->escape($data['billing_type'] ?? 'fixed') . "',
            hourly_rate = '" . (float)($data['hourly_rate'] ?? 0) . "',
            is_billable = '" . (int)($data['is_billable'] ?? 1) . "',
            is_public = '" . (int)($data['is_public'] ?? 0) . "',
            date_modified = NOW()
            WHERE project_id = '" . (int)$project_id . "'");

        // Update project custom fields if provided
        if (isset($data['custom_fields']) && is_array($data['custom_fields'])) {
            $this->deleteProjectCustomFields($project_id);
            $this->addProjectCustomFields($project_id, $data['custom_fields']);
        }

        // Update project tags if provided
        if (isset($data['tags']) && is_array($data['tags'])) {
            $this->deleteProjectTags($project_id);
            $this->addProjectTags($project_id, $data['tags']);
        }
    }

    public function deleteProject($project_id) {
        // Start transaction
        $this->db->query("START TRANSACTION");

        try {
            // Delete project and related data
            $this->db->query("DELETE FROM " . DB_PREFIX . "project WHERE project_id = '" . (int)$project_id . "'");
            $this->db->query("DELETE FROM " . DB_PREFIX . "project_task WHERE project_id = '" . (int)$project_id . "'");
            $this->db->query("DELETE FROM " . DB_PREFIX . "project_milestone WHERE project_id = '" . (int)$project_id . "'");
            $this->db->query("DELETE FROM " . DB_PREFIX . "project_team_member WHERE project_id = '" . (int)$project_id . "'");
            $this->db->query("DELETE FROM " . DB_PREFIX . "project_time_log WHERE project_id = '" . (int)$project_id . "'");
            $this->db->query("DELETE FROM " . DB_PREFIX . "project_document WHERE project_id = '" . (int)$project_id . "'");
            $this->db->query("DELETE FROM " . DB_PREFIX . "project_risk WHERE project_id = '" . (int)$project_id . "'");
            $this->db->query("DELETE FROM " . DB_PREFIX . "project_custom_field WHERE project_id = '" . (int)$project_id . "'");
            $this->db->query("DELETE FROM " . DB_PREFIX . "project_tag WHERE project_id = '" . (int)$project_id . "'");

            $this->db->query("COMMIT");

        } catch (Exception $e) {
            $this->db->query("ROLLBACK");
            throw $e;
        }
    }

    public function getProject($project_id) {
        $query = $this->db->query("SELECT p.*,
                                          CONCAT(pm.firstname, ' ', pm.lastname) as project_manager_name,
                                          c.firstname as client_firstname, c.lastname as client_lastname,
                                          CONCAT(u.firstname, ' ', u.lastname) as created_by_name
                                  FROM " . DB_PREFIX . "project p
                                  LEFT JOIN " . DB_PREFIX . "user pm ON (p.project_manager_id = pm.user_id)
                                  LEFT JOIN " . DB_PREFIX . "customer c ON (p.client_id = c.customer_id)
                                  LEFT JOIN " . DB_PREFIX . "user u ON (p.created_by = u.user_id)
                                  WHERE p.project_id = '" . (int)$project_id . "'");

        if ($query->num_rows) {
            $project = $query->row;

            // Get project tags
            $project['tags'] = $this->getProjectTags($project_id);

            // Get custom fields
            $project['custom_fields'] = $this->getProjectCustomFields($project_id);

            // Get project statistics
            $project['statistics'] = $this->getProjectStatisticsById($project_id);

            return $project;
        }

        return false;
    }

    public function getProjects($data = array()) {
        $sql = "SELECT p.*,
                       CONCAT(pm.firstname, ' ', pm.lastname) as project_manager_name,
                       c.firstname as client_firstname, c.lastname as client_lastname,
                       COUNT(pt.task_id) as total_tasks,
                       COUNT(CASE WHEN pt.status = 'completed' THEN 1 END) as completed_tasks
                FROM " . DB_PREFIX . "project p
                LEFT JOIN " . DB_PREFIX . "user pm ON (p.project_manager_id = pm.user_id)
                LEFT JOIN " . DB_PREFIX . "customer c ON (p.client_id = c.customer_id)
                LEFT JOIN " . DB_PREFIX . "project_task pt ON (p.project_id = pt.project_id)
                WHERE 1=1";

        if (!empty($data['filter_name'])) {
            $sql .= " AND p.name LIKE '%" . $this->db->escape($data['filter_name']) . "%'";
        }

        if (!empty($data['filter_status'])) {
            $sql .= " AND p.status = '" . $this->db->escape($data['filter_status']) . "'";
        }

        if (!empty($data['filter_priority'])) {
            $sql .= " AND p.priority = '" . $this->db->escape($data['filter_priority']) . "'";
        }

        if (!empty($data['filter_client_id'])) {
            $sql .= " AND p.client_id = '" . (int)$data['filter_client_id'] . "'";
        }

        if (!empty($data['filter_project_manager_id'])) {
            $sql .= " AND p.project_manager_id = '" . (int)$data['filter_project_manager_id'] . "'";
        }

        if (!empty($data['filter_date_from'])) {
            $sql .= " AND DATE(p.start_date) >= '" . $this->db->escape($data['filter_date_from']) . "'";
        }

        if (!empty($data['filter_date_to'])) {
            $sql .= " AND DATE(p.end_date) <= '" . $this->db->escape($data['filter_date_to']) . "'";
        }

        $sql .= " GROUP BY p.project_id";

        $sort_data = array(
            'name',
            'status',
            'priority',
            'start_date',
            'end_date',
            'budget',
            'progress',
            'date_added'
        );

        if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
            $sql .= " ORDER BY p." . $data['sort'];
        } else {
            $sql .= " ORDER BY p.date_added";
        }

        if (isset($data['order']) && ($data['order'] == 'DESC')) {
            $sql .= " DESC";
        } else {
            $sql .= " ASC";
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

    public function getTotalProjects($data = array()) {
        $sql = "SELECT COUNT(DISTINCT p.project_id) AS total FROM " . DB_PREFIX . "project p WHERE 1=1";

        if (!empty($data['filter_name'])) {
            $sql .= " AND p.name LIKE '%" . $this->db->escape($data['filter_name']) . "%'";
        }

        if (!empty($data['filter_status'])) {
            $sql .= " AND p.status = '" . $this->db->escape($data['filter_status']) . "'";
        }

        if (!empty($data['filter_priority'])) {
            $sql .= " AND p.priority = '" . $this->db->escape($data['filter_priority']) . "'";
        }

        if (!empty($data['filter_client_id'])) {
            $sql .= " AND p.client_id = '" . (int)$data['filter_client_id'] . "'";
        }

        if (!empty($data['filter_project_manager_id'])) {
            $sql .= " AND p.project_manager_id = '" . (int)$data['filter_project_manager_id'] . "'";
        }

        if (!empty($data['filter_date_from'])) {
            $sql .= " AND DATE(p.start_date) >= '" . $this->db->escape($data['filter_date_from']) . "'";
        }

        if (!empty($data['filter_date_to'])) {
            $sql .= " AND DATE(p.end_date) <= '" . $this->db->escape($data['filter_date_to']) . "'";
        }

        $query = $this->db->query($sql);

        return $query->row['total'];
    }

    public function getProjectStatistics() {
        $sql = "SELECT
                    COUNT(*) as total_projects,
                    COUNT(CASE WHEN status = 'planning' THEN 1 END) as planning_projects,
                    COUNT(CASE WHEN status = 'in_progress' THEN 1 END) as active_projects,
                    COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_projects,
                    COUNT(CASE WHEN status = 'on_hold' THEN 1 END) as on_hold_projects,
                    COUNT(CASE WHEN status = 'cancelled' THEN 1 END) as cancelled_projects,
                    COUNT(CASE WHEN end_date < CURDATE() AND status != 'completed' THEN 1 END) as overdue_projects,
                    AVG(progress) as average_progress,
                    SUM(budget) as total_budget,
                    COUNT(CASE WHEN DATE(date_added) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) THEN 1 END) as new_projects_month
                FROM " . DB_PREFIX . "project";

        $query = $this->db->query($sql);

        return $query->row;
    }

    public function getActiveProjects($limit = 10) {
        $sql = "SELECT
                    p.project_id,
                    p.name,
                    p.status,
                    p.priority,
                    p.progress,
                    p.start_date,
                    p.end_date,
                    p.budget,
                    CONCAT(pm.firstname, ' ', pm.lastname) as project_manager_name,
                    DATEDIFF(p.end_date, CURDATE()) as days_remaining,
                    COUNT(pt.task_id) as total_tasks,
                    COUNT(CASE WHEN pt.status = 'completed' THEN 1 END) as completed_tasks
                FROM " . DB_PREFIX . "project p
                LEFT JOIN " . DB_PREFIX . "user pm ON (p.project_manager_id = pm.user_id)
                LEFT JOIN " . DB_PREFIX . "project_task pt ON (p.project_id = pt.project_id)
                WHERE p.status IN ('planning', 'in_progress')
                GROUP BY p.project_id
                ORDER BY p.priority DESC, p.end_date ASC
                LIMIT " . (int)$limit;

        $query = $this->db->query($sql);

        return $query->rows;
    }

    public function getOverdueTasks($limit = 10) {
        $sql = "SELECT
                    pt.task_id,
                    pt.title,
                    pt.due_date,
                    pt.priority,
                    pt.status,
                    p.name as project_name,
                    CONCAT(u.firstname, ' ', u.lastname) as assigned_to_name,
                    DATEDIFF(CURDATE(), pt.due_date) as days_overdue
                FROM " . DB_PREFIX . "project_task pt
                LEFT JOIN " . DB_PREFIX . "project p ON (pt.project_id = p.project_id)
                LEFT JOIN " . DB_PREFIX . "user u ON (pt.assigned_to = u.user_id)
                WHERE pt.due_date < CURDATE()
                AND pt.status NOT IN ('completed', 'cancelled')
                ORDER BY days_overdue DESC, pt.priority DESC
                LIMIT " . (int)$limit;

        $query = $this->db->query($sql);

        return $query->rows;
    }

    public function getUpcomingMilestones($limit = 10) {
        $sql = "SELECT
                    pm.milestone_id,
                    pm.name,
                    pm.due_date,
                    pm.status,
                    p.name as project_name,
                    DATEDIFF(pm.due_date, CURDATE()) as days_until_due
                FROM " . DB_PREFIX . "project_milestone pm
                LEFT JOIN " . DB_PREFIX . "project p ON (pm.project_id = p.project_id)
                WHERE pm.due_date >= CURDATE()
                AND pm.status != 'completed'
                ORDER BY pm.due_date ASC
                LIMIT " . (int)$limit;

        $query = $this->db->query($sql);

        return $query->rows;
    }

    public function getTeamWorkload() {
        $sql = "SELECT
                    u.user_id,
                    CONCAT(u.firstname, ' ', u.lastname) as user_name,
                    COUNT(DISTINCT ptm.project_id) as active_projects,
                    COUNT(pt.task_id) as assigned_tasks,
                    COUNT(CASE WHEN pt.status = 'in_progress' THEN 1 END) as active_tasks,
                    COUNT(CASE WHEN pt.due_date < CURDATE() AND pt.status NOT IN ('completed', 'cancelled') THEN 1 END) as overdue_tasks,
                    SUM(CASE WHEN ptl.date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) THEN ptl.hours ELSE 0 END) as hours_this_week
                FROM " . DB_PREFIX . "user u
                LEFT JOIN " . DB_PREFIX . "project_team_member ptm ON (u.user_id = ptm.user_id)
                LEFT JOIN " . DB_PREFIX . "project_task pt ON (u.user_id = pt.assigned_to)
                LEFT JOIN " . DB_PREFIX . "project_time_log ptl ON (u.user_id = ptl.user_id)
                WHERE u.status = 1
                GROUP BY u.user_id
                HAVING active_projects > 0 OR assigned_tasks > 0
                ORDER BY active_tasks DESC, overdue_tasks DESC";

        $query = $this->db->query($sql);

        return $query->rows;
    }

    public function getPerformanceMetrics() {
        $sql = "SELECT
                    COUNT(CASE WHEN p.status = 'completed' AND p.end_date <= p.original_end_date THEN 1 END) as on_time_projects,
                    COUNT(CASE WHEN p.status = 'completed' AND p.end_date > p.original_end_date THEN 1 END) as delayed_projects,
                    COUNT(CASE WHEN p.status = 'completed' THEN 1 END) as total_completed,
                    AVG(CASE WHEN p.status = 'completed' THEN p.progress END) as avg_completion_rate,
                    AVG(CASE WHEN p.status = 'completed' THEN DATEDIFF(p.end_date, p.start_date) END) as avg_project_duration,
                    SUM(CASE WHEN p.status = 'completed' THEN p.budget END) as completed_project_value
                FROM " . DB_PREFIX . "project p
                WHERE p.date_added >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)";

        $query = $this->db->query($sql);

        return $query->row;
    }

    public function getRecentActivities($limit = 15) {
        $sql = "SELECT
                    'task_created' as activity_type,
                    pt.title as activity_description,
                    p.name as project_name,
                    CONCAT(u.firstname, ' ', u.lastname) as user_name,
                    pt.date_added as activity_date
                FROM " . DB_PREFIX . "project_task pt
                LEFT JOIN " . DB_PREFIX . "project p ON (pt.project_id = p.project_id)
                LEFT JOIN " . DB_PREFIX . "user u ON (pt.created_by = u.user_id)
                WHERE pt.date_added >= DATE_SUB(NOW(), INTERVAL 7 DAY)

                UNION ALL

                SELECT
                    'time_logged' as activity_type,
                    CONCAT('Logged ', ptl.hours, ' hours') as activity_description,
                    p.name as project_name,
                    CONCAT(u.firstname, ' ', u.lastname) as user_name,
                    ptl.date_added as activity_date
                FROM " . DB_PREFIX . "project_time_log ptl
                LEFT JOIN " . DB_PREFIX . "project p ON (ptl.project_id = p.project_id)
                LEFT JOIN " . DB_PREFIX . "user u ON (ptl.user_id = u.user_id)
                WHERE ptl.date_added >= DATE_SUB(NOW(), INTERVAL 7 DAY)

                UNION ALL

                SELECT
                    'milestone_completed' as activity_type,
                    CONCAT('Milestone completed: ', pm.name) as activity_description,
                    p.name as project_name,
                    CONCAT(u.firstname, ' ', u.lastname) as user_name,
                    pm.date_completed as activity_date
                FROM " . DB_PREFIX . "project_milestone pm
                LEFT JOIN " . DB_PREFIX . "project p ON (pm.project_id = p.project_id)
                LEFT JOIN " . DB_PREFIX . "user u ON (pm.completed_by = u.user_id)
                WHERE pm.date_completed >= DATE_SUB(NOW(), INTERVAL 7 DAY)

                ORDER BY activity_date DESC
                LIMIT " . (int)$limit;

        $query = $this->db->query($sql);

        return $query->rows;
    }

    public function getProjectTasks($project_id) {
        $sql = "SELECT
                    pt.*,
                    CONCAT(u.firstname, ' ', u.lastname) as assigned_to_name,
                    CONCAT(c.firstname, ' ', c.lastname) as created_by_name,
                    COUNT(ptd.dependency_id) as dependency_count
                FROM " . DB_PREFIX . "project_task pt
                LEFT JOIN " . DB_PREFIX . "user u ON (pt.assigned_to = u.user_id)
                LEFT JOIN " . DB_PREFIX . "user c ON (pt.created_by = c.user_id)
                LEFT JOIN " . DB_PREFIX . "project_task_dependency ptd ON (pt.task_id = ptd.task_id)
                WHERE pt.project_id = '" . (int)$project_id . "'
                GROUP BY pt.task_id
                ORDER BY pt.sort_order, pt.date_added";

        $query = $this->db->query($sql);

        return $query->rows;
    }

    public function getProjectMilestones($project_id) {
        $sql = "SELECT
                    pm.*,
                    CONCAT(u.firstname, ' ', u.lastname) as completed_by_name
                FROM " . DB_PREFIX . "project_milestone pm
                LEFT JOIN " . DB_PREFIX . "user u ON (pm.completed_by = u.user_id)
                WHERE pm.project_id = '" . (int)$project_id . "'
                ORDER BY pm.due_date";

        $query = $this->db->query($sql);

        return $query->rows;
    }

    public function getProjectTeamMembers($project_id) {
        $sql = "SELECT
                    ptm.*,
                    CONCAT(u.firstname, ' ', u.lastname) as user_name,
                    u.email,
                    COUNT(pt.task_id) as assigned_tasks,
                    COUNT(CASE WHEN pt.status = 'completed' THEN 1 END) as completed_tasks,
                    SUM(ptl.hours) as total_hours
                FROM " . DB_PREFIX . "project_team_member ptm
                LEFT JOIN " . DB_PREFIX . "user u ON (ptm.user_id = u.user_id)
                LEFT JOIN " . DB_PREFIX . "project_task pt ON (ptm.user_id = pt.assigned_to AND ptm.project_id = pt.project_id)
                LEFT JOIN " . DB_PREFIX . "project_time_log ptl ON (ptm.user_id = ptl.user_id AND ptm.project_id = ptl.project_id)
                WHERE ptm.project_id = '" . (int)$project_id . "'
                GROUP BY ptm.user_id
                ORDER BY ptm.role, u.firstname";

        $query = $this->db->query($sql);

        return $query->rows;
    }

    public function getProjectTimeline($project_id) {
        $sql = "SELECT
                    'task' as item_type,
                    pt.task_id as item_id,
                    pt.title as item_name,
                    pt.start_date,
                    pt.due_date as end_date,
                    pt.status,
                    pt.progress,
                    pt.priority
                FROM " . DB_PREFIX . "project_task pt
                WHERE pt.project_id = '" . (int)$project_id . "'

                UNION ALL

                SELECT
                    'milestone' as item_type,
                    pm.milestone_id as item_id,
                    pm.name as item_name,
                    pm.due_date as start_date,
                    pm.due_date as end_date,
                    pm.status,
                    CASE WHEN pm.status = 'completed' THEN 100 ELSE 0 END as progress,
                    'high' as priority
                FROM " . DB_PREFIX . "project_milestone pm
                WHERE pm.project_id = '" . (int)$project_id . "'

                ORDER BY start_date";

        $query = $this->db->query($sql);

        return $query->rows;
    }

    public function getProjectBudget($project_id) {
        $sql = "SELECT
                    p.budget as total_budget,
                    p.currency,
                    SUM(ptl.hours * ptl.hourly_rate) as actual_cost,
                    COUNT(DISTINCT ptl.user_id) as team_members,
                    SUM(ptl.hours) as total_hours,
                    AVG(ptl.hourly_rate) as avg_hourly_rate
                FROM " . DB_PREFIX . "project p
                LEFT JOIN " . DB_PREFIX . "project_time_log ptl ON (p.project_id = ptl.project_id)
                WHERE p.project_id = '" . (int)$project_id . "'
                GROUP BY p.project_id";

        $query = $this->db->query($sql);

        return $query->row;
    }

    public function getProjectDocuments($project_id) {
        $sql = "SELECT
                    pd.*,
                    CONCAT(u.firstname, ' ', u.lastname) as uploaded_by_name
                FROM " . DB_PREFIX . "project_document pd
                LEFT JOIN " . DB_PREFIX . "user u ON (pd.uploaded_by = u.user_id)
                WHERE pd.project_id = '" . (int)$project_id . "'
                ORDER BY pd.date_added DESC";

        $query = $this->db->query($sql);

        return $query->rows;
    }

    public function getProjectRisks($project_id) {
        $sql = "SELECT
                    pr.*,
                    CONCAT(u.firstname, ' ', u.lastname) as identified_by_name,
                    CONCAT(o.firstname, ' ', o.lastname) as owner_name
                FROM " . DB_PREFIX . "project_risk pr
                LEFT JOIN " . DB_PREFIX . "user u ON (pr.identified_by = u.user_id)
                LEFT JOIN " . DB_PREFIX . "user o ON (pr.owner_id = o.user_id)
                WHERE pr.project_id = '" . (int)$project_id . "'
                ORDER BY (pr.probability * pr.impact) DESC, pr.date_identified DESC";

        $query = $this->db->query($sql);

        return $query->rows;
    }