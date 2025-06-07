<?php
/**
 * نموذج إدارة الفرق والأقسام المتكامل مع Workflow
 * Advanced Teams & Departments Management Model
 * 
 * نموذج البيانات للنظام الإلكتروني المتكامل الذي يحل محل الأوراق
 * مع تكامل كامل مع workflow والإشعارات
 * 
 * @package    AYM ERP
 * @author     AYM ERP Development Team
 * @copyright  2024 AYM ERP
 * @license    Proprietary
 * @version    1.0.0
 * @link       https://aym-erp.com
 * @since      2024-12-19
 */

class ModelCommunicationTeams extends Model {
    
    /**
     * الحصول على الأقسام
     */
    public function getDepartments() {
        $query = $this->db->query("
            SELECT ug.user_group_id as department_id, ug.name as department_name,
                   COUNT(DISTINCT u.user_id) as members_count,
                   COUNT(DISTINCT wr.request_id) as active_requests
            FROM cod_user_group ug
            LEFT JOIN cod_user_to_group utg ON (ug.user_group_id = utg.user_group_id)
            LEFT JOIN cod_user u ON (utg.user_id = u.user_id AND u.status = 1)
            LEFT JOIN cod_workflow_request wr ON (wr.status IN ('pending', 'in_progress'))
            WHERE ug.status = 1
            GROUP BY ug.user_group_id
            ORDER BY ug.name
        ");
        
        return $query->rows;
    }
    
    /**
     * الحصول على الفرق النشطة
     */
    public function getActiveTeams() {
        $query = $this->db->query("
            SELECT t.*, COUNT(tm.user_id) as members_count
            FROM cod_team t
            LEFT JOIN cod_team_member tm ON (t.team_id = tm.team_id)
            WHERE t.status = 'active'
            GROUP BY t.team_id
            ORDER BY t.name
        ");
        
        return $query->rows;
    }
    
    /**
     * الحصول على طلبات الموافقة المعلقة للمستخدم
     */
    public function getPendingApprovals($user_id) {
        $query = $this->db->query("
            SELECT wr.*, ws.step_name, ws.instructions,
                   CONCAT(u.firstname, ' ', u.lastname) as requester_name,
                   DATEDIFF(NOW(), wr.created_at) as days_pending
            FROM cod_workflow_request wr
            LEFT JOIN cod_workflow_step ws ON (wr.current_step_id = ws.step_id)
            LEFT JOIN cod_user u ON (wr.requester_id = u.user_id)
            WHERE wr.status IN ('pending', 'in_progress')
            AND (ws.approver_user_id = '" . (int)$user_id . "' 
                 OR ws.approver_group_id IN (
                     SELECT user_group_id FROM cod_user_to_group 
                     WHERE user_id = '" . (int)$user_id . "'
                 ))
            ORDER BY wr.priority DESC, wr.created_at ASC
        ");
        
        return $query->rows;
    }
    
    /**
     * الحصول على المهام المعلقة
     */
    public function getPendingTasks($user_id) {
        $query = $this->db->query("
            SELECT t.*, p.name as project_name
            FROM cod_task t
            LEFT JOIN cod_project p ON (t.project_id = p.project_id)
            WHERE t.assigned_to = '" . (int)$user_id . "'
            AND t.status IN ('pending', 'in_progress')
            ORDER BY t.priority DESC, t.due_date ASC
            LIMIT 10
        ");
        
        return $query->rows;
    }
    
    /**
     * الحصول على الوثائق المطلوب مراجعتها
     */
    public function getPendingDocuments($user_id) {
        $query = $this->db->query("
            SELECT d.*, dp.permission_type
            FROM cod_document d
            LEFT JOIN cod_document_permission dp ON (d.document_id = dp.document_id)
            WHERE dp.user_id = '" . (int)$user_id . "'
            AND dp.permission_type IN ('approve', 'review')
            AND d.status = 'pending_approval'
            ORDER BY d.created_at DESC
            LIMIT 10
        ");
        
        return $query->rows;
    }
    
    /**
     * إنشاء طلب موافقة جديد
     */
    public function createApprovalRequest($data) {
        $this->db->query("
            INSERT INTO cod_workflow_request SET
            workflow_id = '" . (int)$this->getWorkflowIdByType($data['request_type']) . "',
            requester_id = '" . (int)$this->user->getId() . "',
            title = '" . $this->db->escape($data['title']) . "',
            description = '" . $this->db->escape($data['description']) . "',
            priority = '" . $this->db->escape($data['priority'] ?? 'normal') . "',
            reference_module = '" . $this->db->escape($data['request_type']) . "',
            reference_id = '" . (int)($data['reference_id'] ?? 0) . "',
            request_data = '" . $this->db->escape(json_encode($data)) . "',
            created_at = NOW()
        ");
        
        $request_id = $this->db->getLastId();
        
        // تحديد الخطوة الأولى
        $first_step = $this->getFirstWorkflowStep($data['request_type']);
        if ($first_step) {
            $this->db->query("
                UPDATE cod_workflow_request SET
                current_step_id = '" . (int)$first_step['step_id'] . "'
                WHERE request_id = '" . (int)$request_id . "'
            ");
        }
        
        return $request_id;
    }
    
    /**
     * معالجة موافقة أو رفض طلب
     */
    public function processApproval($data) {
        try {
            // تسجيل الموافقة
            $this->db->query("
                INSERT INTO cod_workflow_approval SET
                request_id = '" . (int)$data['request_id'] . "',
                step_id = (SELECT current_step_id FROM cod_workflow_request WHERE request_id = '" . (int)$data['request_id'] . "'),
                user_id = '" . (int)$data['user_id'] . "',
                action = '" . $this->db->escape($data['action']) . "',
                comment = '" . $this->db->escape($data['comment'] ?? '') . "',
                delegated_to = '" . (int)($data['delegated_to'] ?? 0) . "',
                created_at = NOW()
            ");
            
            // تحديث حالة الطلب
            if ($data['action'] == 'approved') {
                $next_step = $this->getNextWorkflowStep($data['request_id']);
                if ($next_step) {
                    // الانتقال للخطوة التالية
                    $this->db->query("
                        UPDATE cod_workflow_request SET
                        current_step_id = '" . (int)$next_step['step_id'] . "',
                        status = 'in_progress',
                        updated_at = NOW()
                        WHERE request_id = '" . (int)$data['request_id'] . "'
                    ");
                } else {
                    // إكمال الطلب
                    $this->db->query("
                        UPDATE cod_workflow_request SET
                        status = 'approved',
                        completed_at = NOW(),
                        updated_at = NOW()
                        WHERE request_id = '" . (int)$data['request_id'] . "'
                    ");
                }
            } elseif ($data['action'] == 'rejected') {
                $this->db->query("
                    UPDATE cod_workflow_request SET
                    status = 'rejected',
                    completed_at = NOW(),
                    updated_at = NOW()
                    WHERE request_id = '" . (int)$data['request_id'] . "'
                ");
            }
            
            return array('success' => true);
            
        } catch (Exception $e) {
            return array('success' => false, 'error' => $e->getMessage());
        }
    }
    
    /**
     * الحصول على معتمدي الطلب للخطوة المحددة
     */
    public function getRequestApprovers($request_id, $step_number = null) {
        $sql = "
            SELECT DISTINCT u.user_id, CONCAT(u.firstname, ' ', u.lastname) as name, u.email
            FROM cod_workflow_request wr
            LEFT JOIN cod_workflow_step ws ON (wr.workflow_id = ws.workflow_id)
            LEFT JOIN cod_user u ON (ws.approver_user_id = u.user_id)
            WHERE wr.request_id = '" . (int)$request_id . "'
        ";
        
        if ($step_number) {
            $sql .= " AND ws.step_order = '" . (int)$step_number . "'";
        } else {
            $sql .= " AND ws.step_id = wr.current_step_id";
        }
        
        $sql .= "
            UNION
            SELECT DISTINCT u.user_id, CONCAT(u.firstname, ' ', u.lastname) as name, u.email
            FROM cod_workflow_request wr
            LEFT JOIN cod_workflow_step ws ON (wr.workflow_id = ws.workflow_id)
            LEFT JOIN cod_user_to_group utg ON (ws.approver_group_id = utg.user_group_id)
            LEFT JOIN cod_user u ON (utg.user_id = u.user_id)
            WHERE wr.request_id = '" . (int)$request_id . "'
        ";
        
        if ($step_number) {
            $sql .= " AND ws.step_order = '" . (int)$step_number . "'";
        } else {
            $sql .= " AND ws.step_id = wr.current_step_id";
        }
        
        $query = $this->db->query($sql);
        
        return $query->rows;
    }
    
    /**
     * الحصول على إحصائيات سير العمل
     */
    public function getTotalPendingApprovals() {
        $query = $this->db->query("
            SELECT COUNT(*) as total
            FROM cod_workflow_request
            WHERE status IN ('pending', 'in_progress')
        ");
        
        return $query->row['total'];
    }
    
    public function getCompletedToday() {
        $query = $this->db->query("
            SELECT COUNT(*) as total
            FROM cod_workflow_request
            WHERE status IN ('approved', 'rejected')
            AND DATE(completed_at) = CURDATE()
        ");
        
        return $query->row['total'];
    }
    
    public function getOverdueTasks() {
        $query = $this->db->query("
            SELECT COUNT(*) as total
            FROM cod_workflow_request
            WHERE status IN ('pending', 'in_progress')
            AND created_at < DATE_SUB(NOW(), INTERVAL 3 DAY)
        ");
        
        return $query->row['total'];
    }
    
    public function getActiveWorkflows() {
        $query = $this->db->query("
            SELECT COUNT(DISTINCT workflow_id) as total
            FROM cod_workflow_request
            WHERE status IN ('pending', 'in_progress')
        ");
        
        return $query->row['total'];
    }
    
    /**
     * الحصول على عدد أعضاء الفريق
     */
    public function getTeamMembersCount($team_type) {
        $query = $this->db->query("
            SELECT COUNT(*) as total
            FROM cod_user_to_group utg
            LEFT JOIN cod_user_group ug ON (utg.user_group_id = ug.user_group_id)
            WHERE ug.name LIKE '%" . $this->db->escape($team_type) . "%'
        ");
        
        return $query->row['total'];
    }
    
    /**
     * الحصول على سير العمل النشط للفريق
     */
    public function getTeamActiveWorkflows($team_type) {
        $query = $this->db->query("
            SELECT COUNT(*) as total
            FROM cod_workflow_request wr
            LEFT JOIN cod_unified_workflow uw ON (wr.workflow_id = uw.workflow_id)
            WHERE wr.status IN ('pending', 'in_progress')
            AND uw.workflow_type LIKE '%" . $this->db->escape($team_type) . "%'
        ");
        
        return $query->row['total'];
    }
    
    /**
     * دوال مساعدة
     */
    private function getWorkflowIdByType($request_type) {
        $query = $this->db->query("
            SELECT workflow_id FROM cod_unified_workflow
            WHERE workflow_type = '" . $this->db->escape($request_type) . "'
            AND status = 'active'
            LIMIT 1
        ");
        
        return $query->num_rows ? $query->row['workflow_id'] : 1;
    }
    
    private function getFirstWorkflowStep($request_type) {
        $workflow_id = $this->getWorkflowIdByType($request_type);
        
        $query = $this->db->query("
            SELECT * FROM cod_workflow_step
            WHERE workflow_id = '" . (int)$workflow_id . "'
            ORDER BY step_order ASC
            LIMIT 1
        ");
        
        return $query->num_rows ? $query->row : null;
    }
    
    private function getNextWorkflowStep($request_id) {
        $query = $this->db->query("
            SELECT ws.* FROM cod_workflow_step ws
            LEFT JOIN cod_workflow_request wr ON (ws.workflow_id = wr.workflow_id)
            LEFT JOIN cod_workflow_step current_ws ON (wr.current_step_id = current_ws.step_id)
            WHERE wr.request_id = '" . (int)$request_id . "'
            AND ws.step_order > current_ws.step_order
            ORDER BY ws.step_order ASC
            LIMIT 1
        ");
        
        return $query->num_rows ? $query->row : null;
    }
}
