<?php
/**
 * AYM ERP - Advanced Human Resources Management Model
 *
 * Professional HRM system with comprehensive employee lifecycle management
 * Features:
 * - Complete employee information management
 * - Advanced attendance and time tracking
 * - Payroll integration and salary management
 * - Performance evaluation and appraisals
 * - Leave management with approval workflows
 * - Training and development tracking
 * - Document management and compliance
 * - Employee self-service portal
 *
 * @author AYM ERP Development Team
 * @copyright 2024 AYM ERP
 * @license Commercial License
 * @version 1.0.0
 * @link https://aym-erp.com
 */

class ModelHrEmployee extends Model {

    public function getTotalEmployees($filter_data = array()) {
        $sql = "SELECT COUNT(*) AS total FROM `cod_employee_profile` ep
                LEFT JOIN `cod_user` u ON (ep.user_id = u.user_id)
                WHERE 1";

        if (!empty($filter_data['filter_name'])) {
            $sql .= " AND (u.firstname LIKE '%" . $this->db->escape($filter_data['filter_name']) . "%'
                      OR u.lastname LIKE '%" . $this->db->escape($filter_data['filter_name']) . "%')";
        }

        if (!empty($filter_data['filter_status'])) {
            $sql .= " AND ep.status = '" . $this->db->escape($filter_data['filter_status']) . "'";
        }

        $query = $this->db->query($sql);
        return $query->row['total'];
    }

    public function getEmployees($filter_data = array()) {
        $sql = "SELECT ep.*, CONCAT(u.firstname,' ',u.lastname) as employee_name
                FROM `cod_employee_profile` ep
                LEFT JOIN `cod_user` u ON (ep.user_id = u.user_id)
                WHERE 1";

        if (!empty($filter_data['filter_name'])) {
            $sql .= " AND (u.firstname LIKE '%" . $this->db->escape($filter_data['filter_name']) . "%'
                      OR u.lastname LIKE '%" . $this->db->escape($filter_data['filter_name']) . "%')";
        }

        if (!empty($filter_data['filter_status'])) {
            $sql .= " AND ep.status = '" . $this->db->escape($filter_data['filter_status']) . "'";
        }

        $sort_data = array('employee_name','job_title','status','salary','hiring_date');
        $sort = (isset($filter_data['sort']) && in_array($filter_data['sort'], $sort_data)) ? $filter_data['sort'] : 'hiring_date';
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

    public function getEmployee($employee_id) {
        $query = $this->db->query("SELECT * FROM `cod_employee_profile` WHERE employee_id = '" . (int)$employee_id . "'");
        return $query->row;
    }

    public function addEmployee($data) {
        $this->db->query("INSERT INTO `cod_employee_profile` SET
            user_id = '" . (int)$data['user_id'] . "',
            job_title = '" . $this->db->escape($data['job_title']) . "',
            hiring_date = '" . $this->db->escape($data['hiring_date']) . "',
            salary = '" . (float)$data['salary'] . "',
            status = '" . $this->db->escape($data['status']) . "'");

        return $this->db->getLastId();
    }

    public function editEmployee($employee_id, $data) {
        $this->db->query("UPDATE `cod_employee_profile` SET
            user_id = '" . (int)$data['user_id'] . "',
            job_title = '" . $this->db->escape($data['job_title']) . "',
            hiring_date = '" . $this->db->escape($data['hiring_date']) . "',
            salary = '" . (float)$data['salary'] . "',
            status = '" . $this->db->escape($data['status']) . "'
            WHERE employee_id = '" . (int)$employee_id . "'");
    }

    public function deleteEmployee($employee_id) {
        // يحذف الموظف والمستندات المرتبطة به (بفضل foreign key سيحذف documents آلياً)
        $this->db->query("DELETE FROM `cod_employee_profile` WHERE employee_id = '" . (int)$employee_id . "'");
    }

    public function getEmployeeDocuments($employee_id) {
        $query = $this->db->query("SELECT * FROM `cod_employee_documents` WHERE employee_id = '" . (int)$employee_id . "' ORDER BY date_added DESC");
        return $query->rows;
    }

    public function getEmployeeDocument($document_id) {
        $query = $this->db->query("SELECT * FROM `cod_employee_documents` WHERE document_id = '" . (int)$document_id . "'");
        return $query->row;
    }

    public function addEmployeeDocument($employee_id, $data) {
        $this->db->query("INSERT INTO `cod_employee_documents` SET
            employee_id = '" . (int)$employee_id . "',
            document_name = '" . $this->db->escape($data['document_name']) . "',
            file_path = '" . $this->db->escape($data['file_path']) . "',
            description = '" . $this->db->escape($data['description']) . "'");

        return $this->db->getLastId();
    }

    public function deleteEmployeeDocument($document_id) {
        $this->db->query("DELETE FROM `cod_employee_documents` WHERE document_id = '" . (int)$document_id . "'");
    }

    public function getHRStatistics() {
        $sql = "SELECT
                    COUNT(*) as total_employees,
                    COUNT(CASE WHEN ep.status = 'active' THEN 1 END) as active_employees,
                    COUNT(CASE WHEN ep.status = 'inactive' THEN 1 END) as inactive_employees,
                    COUNT(CASE WHEN ep.status = 'terminated' THEN 1 END) as terminated_employees,
                    COUNT(CASE WHEN DATE(ep.hiring_date) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) THEN 1 END) as new_hires_month,
                    COUNT(CASE WHEN DATE(ep.hiring_date) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) THEN 1 END) as new_hires_week,
                    AVG(ep.salary) as average_salary,
                    COUNT(CASE WHEN u.date_of_birth IS NOT NULL AND
                               MONTH(u.date_of_birth) = MONTH(CURDATE()) AND
                               DAY(u.date_of_birth) >= DAY(CURDATE()) AND
                               DAY(u.date_of_birth) <= DAY(CURDATE()) + 7 THEN 1 END) as upcoming_birthdays
                FROM " . DB_PREFIX . "employee_profile ep
                LEFT JOIN " . DB_PREFIX . "user u ON (ep.user_id = u.user_id)";

        $query = $this->db->query($sql);

        return $query->row;
    }

    public function getAttendanceSummary() {
        $sql = "SELECT
                    COUNT(DISTINCT a.employee_id) as employees_present_today,
                    COUNT(CASE WHEN a.attendance_date = CURDATE() THEN 1 END) as total_attendance_today,
                    COUNT(CASE WHEN a.attendance_date = CURDATE() AND a.status = 'late' THEN 1 END) as late_arrivals_today,
                    COUNT(CASE WHEN a.attendance_date = CURDATE() AND a.status = 'absent' THEN 1 END) as absences_today,
                    AVG(CASE WHEN a.attendance_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
                             THEN TIME_TO_SEC(TIMEDIFF(a.check_out_time, a.check_in_time))/3600 END) as avg_hours_week
                FROM " . DB_PREFIX . "employee_attendance a
                WHERE a.attendance_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";

        $query = $this->db->query($sql);

        return $query->row;
    }

    public function getPendingLeaveRequests($limit = 10) {
        $sql = "SELECT
                    lr.leave_request_id,
                    lr.start_date,
                    lr.end_date,
                    lr.days_requested,
                    lr.reason,
                    lr.date_requested,
                    CONCAT(u.firstname, ' ', u.lastname) as employee_name,
                    lt.name as leave_type_name,
                    ep.job_title
                FROM " . DB_PREFIX . "employee_leave_request lr
                LEFT JOIN " . DB_PREFIX . "employee_profile ep ON (lr.employee_id = ep.employee_id)
                LEFT JOIN " . DB_PREFIX . "user u ON (ep.user_id = u.user_id)
                LEFT JOIN " . DB_PREFIX . "hr_leave_type lt ON (lr.leave_type_id = lt.leave_type_id)
                WHERE lr.status = 'pending'
                ORDER BY lr.date_requested ASC
                LIMIT " . (int)$limit;

        $query = $this->db->query($sql);

        return $query->rows;
    }

    public function getUpcomingBirthdays($limit = 10) {
        $sql = "SELECT
                    ep.employee_id,
                    CONCAT(u.firstname, ' ', u.lastname) as employee_name,
                    u.date_of_birth,
                    ep.job_title,
                    d.name as department_name,
                    DATEDIFF(
                        DATE_ADD(
                            CURDATE(),
                            INTERVAL (
                                DAYOFYEAR(
                                    CONCAT(YEAR(CURDATE()), '-', MONTH(u.date_of_birth), '-', DAY(u.date_of_birth))
                                ) - DAYOFYEAR(CURDATE()) + 365
                            ) % 365 DAY
                        ),
                        CURDATE()
                    ) as days_until_birthday
                FROM " . DB_PREFIX . "employee_profile ep
                LEFT JOIN " . DB_PREFIX . "user u ON (ep.user_id = u.user_id)
                LEFT JOIN " . DB_PREFIX . "hr_department d ON (ep.department_id = d.department_id)
                WHERE u.date_of_birth IS NOT NULL
                AND ep.status = 'active'
                ORDER BY days_until_birthday ASC
                LIMIT " . (int)$limit;

        $query = $this->db->query($sql);

        return $query->rows;
    }

    public function getNewHires($limit = 10) {
        $sql = "SELECT
                    ep.employee_id,
                    CONCAT(u.firstname, ' ', u.lastname) as employee_name,
                    ep.job_title,
                    ep.hiring_date,
                    ep.salary,
                    d.name as department_name,
                    DATEDIFF(CURDATE(), ep.hiring_date) as days_since_hired
                FROM " . DB_PREFIX . "employee_profile ep
                LEFT JOIN " . DB_PREFIX . "user u ON (ep.user_id = u.user_id)
                LEFT JOIN " . DB_PREFIX . "hr_department d ON (ep.department_id = d.department_id)
                WHERE ep.hiring_date >= DATE_SUB(CURDATE(), INTERVAL 90 DAY)
                AND ep.status = 'active'
                ORDER BY ep.hiring_date DESC
                LIMIT " . (int)$limit;

        $query = $this->db->query($sql);

        return $query->rows;
    }

    public function getPerformanceMetrics() {
        $sql = "SELECT
                    COUNT(pr.review_id) as total_reviews,
                    AVG(pr.overall_rating) as average_rating,
                    COUNT(CASE WHEN pr.overall_rating >= 4 THEN 1 END) as excellent_performers,
                    COUNT(CASE WHEN pr.overall_rating >= 3 AND pr.overall_rating < 4 THEN 1 END) as good_performers,
                    COUNT(CASE WHEN pr.overall_rating < 3 THEN 1 END) as needs_improvement,
                    COUNT(CASE WHEN pr.review_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH) THEN 1 END) as reviews_this_year
                FROM " . DB_PREFIX . "employee_performance_review pr
                WHERE pr.status = 'completed'";

        $query = $this->db->query($sql);

        return $query->row;
    }

    public function getDepartmentStatistics() {
        $sql = "SELECT
                    d.department_id,
                    d.name as department_name,
                    COUNT(ep.employee_id) as employee_count,
                    AVG(ep.salary) as average_salary,
                    COUNT(CASE WHEN ep.status = 'active' THEN 1 END) as active_count,
                    COUNT(CASE WHEN lr.status = 'pending' THEN 1 END) as pending_leaves
                FROM " . DB_PREFIX . "hr_department d
                LEFT JOIN " . DB_PREFIX . "employee_profile ep ON (d.department_id = ep.department_id)
                LEFT JOIN " . DB_PREFIX . "employee_leave_request lr ON (ep.employee_id = lr.employee_id AND lr.status = 'pending')
                WHERE d.status = 1
                GROUP BY d.department_id
                ORDER BY employee_count DESC";

        $query = $this->db->query($sql);

        return $query->rows;
    }

    public function getEmployeeAttendance($employee_id, $days = 30) {
        $sql = "SELECT
                    a.*,
                    TIME_TO_SEC(TIMEDIFF(a.check_out_time, a.check_in_time))/3600 as hours_worked
                FROM " . DB_PREFIX . "employee_attendance a
                WHERE a.employee_id = '" . (int)$employee_id . "'
                AND a.attendance_date >= DATE_SUB(CURDATE(), INTERVAL " . (int)$days . " DAY)
                ORDER BY a.attendance_date DESC";

        $query = $this->db->query($sql);

        return $query->rows;
    }

    public function getEmployeeLeaveHistory($employee_id, $limit = 20) {
        $sql = "SELECT
                    lr.*,
                    lt.name as leave_type_name,
                    CONCAT(u.firstname, ' ', u.lastname) as approved_by_name
                FROM " . DB_PREFIX . "employee_leave_request lr
                LEFT JOIN " . DB_PREFIX . "hr_leave_type lt ON (lr.leave_type_id = lt.leave_type_id)
                LEFT JOIN " . DB_PREFIX . "user u ON (lr.approved_by = u.user_id)
                WHERE lr.employee_id = '" . (int)$employee_id . "'
                ORDER BY lr.date_requested DESC
                LIMIT " . (int)$limit;

        $query = $this->db->query($sql);

        return $query->rows;
    }

    public function getEmployeePerformanceReviews($employee_id) {
        $sql = "SELECT
                    pr.*,
                    CONCAT(u.firstname, ' ', u.lastname) as reviewer_name
                FROM " . DB_PREFIX . "employee_performance_review pr
                LEFT JOIN " . DB_PREFIX . "user u ON (pr.reviewer_id = u.user_id)
                WHERE pr.employee_id = '" . (int)$employee_id . "'
                ORDER BY pr.review_date DESC";

        $query = $this->db->query($sql);

        return $query->rows;
    }

    public function getEmployeeTrainingRecords($employee_id) {
        $sql = "SELECT
                    tr.*,
                    t.title as training_title,
                    t.description as training_description
                FROM " . DB_PREFIX . "employee_training_record tr
                LEFT JOIN " . DB_PREFIX . "hr_training t ON (tr.training_id = t.training_id)
                WHERE tr.employee_id = '" . (int)$employee_id . "'
                ORDER BY tr.completion_date DESC";

        $query = $this->db->query($sql);

        return $query->rows;
    }

    public function getEmployeeSalaryHistory($employee_id) {
        $sql = "SELECT
                    sh.*,
                    CONCAT(u.firstname, ' ', u.lastname) as approved_by_name
                FROM " . DB_PREFIX . "employee_salary_history sh
                LEFT JOIN " . DB_PREFIX . "user u ON (sh.approved_by = u.user_id)
                WHERE sh.employee_id = '" . (int)$employee_id . "'
                ORDER BY sh.effective_date DESC";

        $query = $this->db->query($sql);

        return $query->rows;
    }

    public function recordAttendance($data) {
        // Check if attendance already exists for this date
        $existing = $this->db->query("SELECT attendance_id FROM " . DB_PREFIX . "employee_attendance
                                     WHERE employee_id = '" . (int)$data['employee_id'] . "'
                                     AND attendance_date = '" . $this->db->escape($data['attendance_date']) . "'");

        if ($existing->num_rows) {
            // Update existing record
            $this->db->query("UPDATE " . DB_PREFIX . "employee_attendance SET
                             check_in_time = '" . $this->db->escape($data['check_in_time']) . "',
                             check_out_time = '" . $this->db->escape($data['check_out_time'] ?? null) . "',
                             break_time = '" . (int)($data['break_time'] ?? 0) . "',
                             overtime_hours = '" . (float)($data['overtime_hours'] ?? 0) . "',
                             status = '" . $this->db->escape($data['status'] ?? 'present') . "',
                             notes = '" . $this->db->escape($data['notes'] ?? '') . "',
                             date_modified = NOW()
                             WHERE attendance_id = '" . (int)$existing->row['attendance_id'] . "'");

            return $existing->row['attendance_id'];
        } else {
            // Insert new record
            $this->db->query("INSERT INTO " . DB_PREFIX . "employee_attendance SET
                             employee_id = '" . (int)$data['employee_id'] . "',
                             attendance_date = '" . $this->db->escape($data['attendance_date']) . "',
                             check_in_time = '" . $this->db->escape($data['check_in_time']) . "',
                             check_out_time = '" . $this->db->escape($data['check_out_time'] ?? null) . "',
                             break_time = '" . (int)($data['break_time'] ?? 0) . "',
                             overtime_hours = '" . (float)($data['overtime_hours'] ?? 0) . "',
                             status = '" . $this->db->escape($data['status'] ?? 'present') . "',
                             notes = '" . $this->db->escape($data['notes'] ?? '') . "',
                             date_added = NOW()");

            return $this->db->getLastId();
        }
    }

    public function submitLeaveRequest($data) {
        // Calculate days requested
        $start_date = new DateTime($data['start_date']);
        $end_date = new DateTime($data['end_date']);
        $days_requested = $start_date->diff($end_date)->days + 1;

        $this->db->query("INSERT INTO " . DB_PREFIX . "employee_leave_request SET
                         employee_id = '" . (int)$data['employee_id'] . "',
                         leave_type_id = '" . (int)$data['leave_type_id'] . "',
                         start_date = '" . $this->db->escape($data['start_date']) . "',
                         end_date = '" . $this->db->escape($data['end_date']) . "',
                         days_requested = '" . (int)$days_requested . "',
                         reason = '" . $this->db->escape($data['reason']) . "',
                         status = 'pending',
                         date_requested = NOW()");

        return $this->db->getLastId();
    }

    public function processLeaveRequest($leave_id, $action, $comments, $user_id) {
        $status = ($action == 'approve') ? 'approved' : 'rejected';

        $this->db->query("UPDATE " . DB_PREFIX . "employee_leave_request SET
                         status = '" . $this->db->escape($status) . "',
                         approved_by = '" . (int)$user_id . "',
                         approval_comments = '" . $this->db->escape($comments) . "',
                         date_processed = NOW()
                         WHERE leave_request_id = '" . (int)$leave_id . "'");

        return $this->db->countAffected() > 0;
    }

    public function createPerformanceReview($data) {
        $this->db->query("INSERT INTO " . DB_PREFIX . "employee_performance_review SET
                         employee_id = '" . (int)$data['employee_id'] . "',
                         reviewer_id = '" . (int)$data['reviewer_id'] . "',
                         review_period_start = '" . $this->db->escape($data['review_period_start']) . "',
                         review_period_end = '" . $this->db->escape($data['review_period_end']) . "',
                         overall_rating = '" . (float)$data['overall_rating'] . "',
                         goals_achievement = '" . (float)($data['goals_achievement'] ?? 0) . "',
                         communication_skills = '" . (float)($data['communication_skills'] ?? 0) . "',
                         teamwork = '" . (float)($data['teamwork'] ?? 0) . "',
                         leadership = '" . (float)($data['leadership'] ?? 0) . "',
                         technical_skills = '" . (float)($data['technical_skills'] ?? 0) . "',
                         strengths = '" . $this->db->escape($data['strengths'] ?? '') . "',
                         areas_for_improvement = '" . $this->db->escape($data['areas_for_improvement'] ?? '') . "',
                         goals_next_period = '" . $this->db->escape($data['goals_next_period'] ?? '') . "',
                         comments = '" . $this->db->escape($data['comments'] ?? '') . "',
                         status = 'completed',
                         review_date = NOW()");

        return $this->db->getLastId();
    }

}
