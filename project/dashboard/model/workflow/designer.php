<?php
/**
 * نظام أيم ERP: نظام إدارة موارد الشركات المتكامل للتجارة والتوزيع
 * نموذج محرر سير العمل المرئي
 */
class ModelWorkflowDesigner extends Model {
    /**
     * إضافة سير عمل جديد
     * 
     * @param array $data بيانات سير العمل
     * @return int معرف سير العمل الجديد
     */
    public function addWorkflow($data) {
        $this->db->query("INSERT INTO " . DB_PREFIX . "unified_workflow SET 
            name = '" . $this->db->escape($data['name']) . "',
            description = '" . $this->db->escape($data['description']) . "',
            workflow_type = '" . $this->db->escape($data['workflow_type']) . "',
            status = '" . $this->db->escape($data['status']) . "',
            creator_id = '" . (int)$this->user->getId() . "',
            created_at = NOW(),
            updated_at = NOW(),
            is_template = '0',
            department_id = " . (isset($data['department_id']) && $data['department_id'] ? "'" . (int)$data['department_id'] . "'" : "NULL") . ",
            escalation_enabled = '" . (int)$data['escalation_enabled'] . "',
            escalation_after_days = " . (isset($data['escalation_after_days']) && $data['escalation_after_days'] ? "'" . (int)$data['escalation_after_days'] . "'" : "NULL") . ",
            notify_creator = '" . (int)$data['notify_creator'] . "'");
        
        $workflow_id = $this->db->getLastId();
        
        // تخزين بيانات سير العمل المرئي (التصميم المرئي)
        if (isset($data['workflow_data'])) {
            $this->db->query("INSERT INTO " . DB_PREFIX . "workflow_designer_data SET 
                workflow_id = '" . (int)$workflow_id . "',
                workflow_data = '" . $this->db->escape($data['workflow_data']) . "',
                created_at = NOW(),
                updated_at = NOW()");
        }
        
        // إنشاء خطوات سير العمل من البيانات المرئية
        $this->generateWorkflowStepsFromDesign($workflow_id, $data);
        
        // تسجيل الحدث
        $this->load->model('tool/activity');
        $this->model_tool_activity->addActivity('workflow', 'create', 'تم إنشاء سير عمل جديد: ' . $data['name'], $this->user->getId());
        
        return $workflow_id;
    }
    
    /**
     * تحديث سير عمل موجود
     * 
     * @param int $workflow_id معرف سير العمل
     * @param array $data بيانات سير العمل
     * @return void
     */
    public function updateWorkflow($workflow_id, $data) {
        $this->db->query("UPDATE " . DB_PREFIX . "unified_workflow SET 
            name = '" . $this->db->escape($data['name']) . "',
            description = '" . $this->db->escape($data['description']) . "',
            workflow_type = '" . $this->db->escape($data['workflow_type']) . "',
            status = '" . $this->db->escape($data['status']) . "',
            updated_at = NOW(),
            department_id = " . (isset($data['department_id']) && $data['department_id'] ? "'" . (int)$data['department_id'] . "'" : "NULL") . ",
            escalation_enabled = '" . (isset($data['escalation_enabled']) ? (int)$data['escalation_enabled'] : 0) . "',
            escalation_after_days = " . (isset($data['escalation_after_days']) && $data['escalation_after_days'] ? "'" . (int)$data['escalation_after_days'] . "'" : "NULL") . ",
            notify_creator = '" . (isset($data['notify_creator']) ? (int)$data['notify_creator'] : 1) . "'
            WHERE workflow_id = '" . (int)$workflow_id . "'");
        
        // تحديث بيانات التصميم المرئي
        if (isset($data['workflow_data'])) {
            $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "workflow_designer_data WHERE workflow_id = '" . (int)$workflow_id . "'");
            
            if ($query->num_rows) {
                $this->db->query("UPDATE " . DB_PREFIX . "workflow_designer_data SET 
                    workflow_data = '" . $this->db->escape($data['workflow_data']) . "',
                    updated_at = NOW()
                    WHERE workflow_id = '" . (int)$workflow_id . "'");
            } else {
                $this->db->query("INSERT INTO " . DB_PREFIX . "workflow_designer_data SET 
                    workflow_id = '" . (int)$workflow_id . "',
                    workflow_data = '" . $this->db->escape($data['workflow_data']) . "',
                    created_at = NOW(),
                    updated_at = NOW()");
            }
        }
        
        // إعادة إنشاء خطوات سير العمل من البيانات المرئية
        if (isset($data['workflow_data'])) {
            // حذف جميع الخطوات القديمة
            $this->db->query("DELETE FROM " . DB_PREFIX . "workflow_step WHERE workflow_id = '" . (int)$workflow_id . "'");
            
            // إنشاء الخطوات الجديدة
            $this->generateWorkflowStepsFromDesign($workflow_id, $data);
        }
        
        // تسجيل الحدث
        $this->load->model('tool/activity');
        $this->model_tool_activity->addActivity('workflow', 'update', 'تم تحديث سير العمل: ' . $data['name'], $this->user->getId());
    }
    
    /**
     * حذف سير عمل
     * 
     * @param int $workflow_id معرف سير العمل
     * @return void
     */
    public function deleteWorkflow($workflow_id) {
        // الحصول على معلومات سير العمل قبل الحذف
        $workflow_info = $this->getWorkflow($workflow_id);
        
        if ($workflow_info) {
            // حذف خطوات سير العمل
            $this->db->query("DELETE FROM " . DB_PREFIX . "workflow_step WHERE workflow_id = '" . (int)$workflow_id . "'");
            
            // حذف بيانات التصميم المرئي
            $this->db->query("DELETE FROM " . DB_PREFIX . "workflow_designer_data WHERE workflow_id = '" . (int)$workflow_id . "'");
            
            // حذف سير العمل نفسه
            $this->db->query("DELETE FROM " . DB_PREFIX . "unified_workflow WHERE workflow_id = '" . (int)$workflow_id . "'");
            
            // تسجيل الحدث
            $this->load->model('tool/activity');
            $this->model_tool_activity->addActivity('workflow', 'delete', 'تم حذف سير العمل: ' . $workflow_info['name'], $this->user->getId());
        }
    }
    
    /**
     * الحصول على معلومات سير عمل
     * 
     * @param int $workflow_id معرف سير العمل
     * @return array بيانات سير العمل
     */
    public function getWorkflow($workflow_id) {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "unified_workflow WHERE workflow_id = '" . (int)$workflow_id . "'");
        
        if ($query->num_rows) {
            return $query->row;
        } else {
            return array();
        }
    }
    
    /**
     * الحصول على بيانات التصميم المرئي لسير العمل
     * 
     * @param int $workflow_id معرف سير العمل
     * @return string بيانات التصميم المرئي بتنسيق JSON
     */
    public function getWorkflowData($workflow_id) {
        $query = $this->db->query("SELECT workflow_data FROM " . DB_PREFIX . "workflow_designer_data WHERE workflow_id = '" . (int)$workflow_id . "'");
        
        if ($query->num_rows) {
            return $query->row['workflow_data'];
        } else {
            return '{}';
        }
    }
    
    /**
     * الحصول على قائمة بجميع سير العمل
     * 
     * @param array $data خيارات التصفية والفرز
     * @return array قائمة سير العمل
     */
    public function getWorkflows($data = array()) {
        $sql = "SELECT w.*, d.name as department_name, CONCAT(u.firstname, ' ', u.lastname) as creator_name 
            FROM " . DB_PREFIX . "unified_workflow w 
            LEFT JOIN " . DB_PREFIX . "department d ON (w.department_id = d.department_id)
            LEFT JOIN " . DB_PREFIX . "user u ON (w.creator_id = u.user_id)";
        
        $sort_data = array(
            'w.name',
            'w.workflow_type',
            'w.status',
            'w.created_at'
        );
        
        if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
            $sql .= " ORDER BY " . $data['sort'];
        } else {
            $sql .= " ORDER BY w.name";
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
    
    /**
     * إنشاء خطوات سير العمل من التصميم المرئي
     * 
     * @param int $workflow_id معرف سير العمل
     * @param array $data بيانات سير العمل
     * @return void
     */
    protected function generateWorkflowStepsFromDesign($workflow_id, $data) {
        if (empty($data['workflow_data'])) {
            return;
        }
        
        // تحويل بيانات التصميم من JSON إلى مصفوفة
        $workflow_design = json_decode($data['workflow_data'], true);
        
        if (!$workflow_design || !isset($workflow_design['nodes']) || !isset($workflow_design['connections'])) {
            return;
        }
        
        // تجهيز العقد
        $nodes = array();
        foreach ($workflow_design['nodes'] as $node) {
            $nodes[$node['id']] = $node;
        }
        
        // بناء خريطة الاتصالات
        $connections = array();
        foreach ($workflow_design['connections'] as $connection) {
            if (!isset($connections[$connection['sourceNodeId']])) {
                $connections[$connection['sourceNodeId']] = array();
            }
            
            $connections[$connection['sourceNodeId']][] = array(
                'target_node_id' => $connection['targetNodeId'],
                'source_handle' => $connection['sourceHandle'],
                'target_handle' => $connection['targetHandle']
            );
        }
        
        // إيجاد عقدة البداية
        $start_node = null;
        foreach ($nodes as $node_id => $node) {
            if ($node['type'] === 'start' || $node['type'] === 'trigger') {
                $start_node = $node;
                break;
            }
        }
        
        if (!$start_node) {
            return; // لا يمكن إنشاء الخطوات بدون عقدة بداية
        }
        
        // بدء ترتيب العقد اعتماداً على تدفق التصميم
        $ordered_nodes = $this->orderNodesByFlow($start_node['id'], $nodes, $connections);
        
        // إنشاء خطوات سير العمل من العقد المرتبة
        $step_order = 1;
        $step_map = array(); // ربط معرفات العقد بمعرفات خطوات سير العمل
        
        foreach ($ordered_nodes as $node_id) {
            $node = $nodes[$node_id];
            
            // تجاهل عقد البداية والتدفق (سنعالجها لاحقاً)
            if (in_array($node['type'], ['start', 'trigger', 'condition', 'delay', 'merge', 'split'])) {
                continue;
            }
            
            // إنشاء خطوة سير العمل
            $step_data = array(
                'workflow_id' => $workflow_id,
                'step_name' => isset($node['data']['name']) ? $node['data']['name'] : $node['type'],
                'step_order' => $step_order,
                'approver_user_id' => isset($node['data']['approver_user_id']) ? $node['data']['approver_user_id'] : null,
                'approver_group_id' => isset($node['data']['approver_group_id']) ? $node['data']['approver_group_id'] : null,
                'approval_type' => isset($node['data']['approval_type']) ? $node['data']['approval_type'] : 'any_one',
                'approval_percentage' => isset($node['data']['approval_percentage']) ? $node['data']['approval_percentage'] : null,
                'is_final_step' => isset($node['data']['is_final_step']) ? (int)$node['data']['is_final_step'] : 0,
                'on_reject_goto_step' => null, // سنحدده لاحقاً بعد إنشاء جميع الخطوات
                'instructions' => isset($node['data']['instructions']) ? $node['data']['instructions'] : null,
                'deadline_days' => isset($node['data']['deadline_days']) ? $node['data']['deadline_days'] : null,
                'reminder_days' => isset($node['data']['reminder_days']) ? $node['data']['reminder_days'] : null
            );
            
            $this->db->query("INSERT INTO " . DB_PREFIX . "workflow_step SET 
                workflow_id = '" . (int)$workflow_id . "',
                step_name = '" . $this->db->escape($step_data['step_name']) . "',
                step_order = '" . (int)$step_data['step_order'] . "',
                approver_user_id = " . ($step_data['approver_user_id'] ? "'" . (int)$step_data['approver_user_id'] . "'" : "NULL") . ",
                approver_group_id = " . ($step_data['approver_group_id'] ? "'" . (int)$step_data['approver_group_id'] . "'" : "NULL") . ",
                approval_type = '" . $this->db->escape($step_data['approval_type']) . "',
                approval_percentage = " . ($step_data['approval_percentage'] ? "'" . (int)$step_data['approval_percentage'] . "'" : "NULL") . ",
                is_final_step = '" . (int)$step_data['is_final_step'] . "',
                on_reject_goto_step = NULL,
                instructions = " . ($step_data['instructions'] ? "'" . $this->db->escape($step_data['instructions']) . "'" : "NULL") . ",
                deadline_days = " . ($step_data['deadline_days'] ? "'" . (int)$step_data['deadline_days'] . "'" : "NULL") . ",
                reminder_days = " . ($step_data['reminder_days'] ? "'" . (int)$step_data['reminder_days'] . "'" : "NULL"));
            
            $step_id = $this->db->getLastId();
            $step_map[$node_id] = $step_id;
            
            $step_order++;
        }
        
        // تحديث خطوات الرفض (on_reject_goto_step)
        foreach ($nodes as $node_id => $node) {
            if (isset($node['data']['on_reject_node_id']) && isset($step_map[$node_id]) && isset($step_map[$node['data']['on_reject_node_id']])) {
                $step_id = $step_map[$node_id];
                $reject_step_id = $step_map[$node['data']['on_reject_node_id']];
                
                $this->db->query("UPDATE " . DB_PREFIX . "workflow_step SET 
                    on_reject_goto_step = '" . (int)$reject_step_id . "'
                    WHERE step_id = '" . (int)$step_id . "'");
            }
        }
    }
    
    /**
     * ترتيب العقد حسب تدفق سير العمل
     * 
     * @param string $start_node_id معرف عقدة البداية
     * @param array $nodes قائمة جميع العقد
     * @param array $connections خريطة الاتصالات
     * @return array قائمة معرفات العقد مرتبة حسب التدفق
     */
    protected function orderNodesByFlow($start_node_id, $nodes, $connections) {
        $ordered_nodes = array();
        $visited = array();
        
        // دالة مساعدة لاستكشاف العقد بشكل متعمق
        $explore = function($node_id) use (&$explore, &$ordered_nodes, &$visited, $nodes, $connections) {
            if (isset($visited[$node_id])) {
                return;
            }
            
            $visited[$node_id] = true;
            $ordered_nodes[] = $node_id;
            
            if (isset($connections[$node_id])) {
                foreach ($connections[$node_id] as $connection) {
                    $target_node_id = $connection['target_node_id'];
                    $explore($target_node_id);
                }
            }
        };
        
        // بدء الاستكشاف من عقدة البداية
        $explore($start_node_id);
        
        return $ordered_nodes;
    }
    
    /**
     * الحصول على إجمالي عدد سير العمل
     * 
     * @return int إجمالي العدد
     */
    public function getTotalWorkflows() {
        $query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "unified_workflow");
        
        return $query->row['total'];
    }
    
    /**
     * إنشاء جدول بيانات التصميم المرئي إذا لم يكن موجوداً
     * 
     * @return void
     */
    public function createDesignerDataTable() {
        $this->db->query("
            CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "workflow_designer_data` (
              `workflow_id` int NOT NULL,
              `workflow_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
              `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
              `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
              PRIMARY KEY (`workflow_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
        ");
        
        // إضافة العلاقة مع جدول سير العمل الموحد
        $query = $this->db->query("
            SELECT * 
            FROM information_schema.TABLE_CONSTRAINTS 
            WHERE CONSTRAINT_SCHEMA = DATABASE() 
            AND TABLE_NAME = '" . DB_PREFIX . "workflow_designer_data' 
            AND CONSTRAINT_NAME = 'fk_workflow_designer_data_workflow'
        ");
        
        if ($query->num_rows == 0) {
            $this->db->query("
                ALTER TABLE `" . DB_PREFIX . "workflow_designer_data`
                ADD CONSTRAINT `fk_workflow_designer_data_workflow` FOREIGN KEY (`workflow_id`) REFERENCES `" . DB_PREFIX . "unified_workflow` (`workflow_id`) ON DELETE CASCADE ON UPDATE CASCADE
            ");
        }
    }
} 