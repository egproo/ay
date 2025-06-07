<?php
/**
 * محرك سير العمل المرئي المتقدم (شبيه n8n)
 * 
 * يوفر نظام سير عمل مرئي متقدم مع:
 * - محرر مرئي بالسحب والإفلات
 * - عقد متنوعة (Triggers, Actions, Conditions, Loops)
 * - تنفيذ تلقائي ومجدول
 * - تكامل مع جميع أنظمة ERP
 * - مراقبة وتتبع التنفيذ
 * - قوالب جاهزة للاستخدام
 * 
 * @author ERP Team
 * @version 1.0
 * @since 2024
 */

class ModelWorkflowVisualWorkflowEngine extends Model {
    
    /**
     * إنشاء سير عمل جديد
     */
    public function createWorkflow($data) {
        $this->db->query("
            INSERT INTO cod_unified_workflow SET 
            name = '" . $this->db->escape($data['name']) . "',
            description = '" . $this->db->escape($data['description']) . "',
            workflow_type = '" . $this->db->escape($data['workflow_type']) . "',
            workflow_definition = '" . $this->db->escape(json_encode($data['workflow_definition'])) . "',
            trigger_type = '" . $this->db->escape($data['trigger_type']) . "',
            trigger_config = '" . $this->db->escape(json_encode($data['trigger_config'])) . "',
            status = '" . $this->db->escape($data['status']) . "',
            creator_id = '" . (int)$this->user->getId() . "',
            department_id = '" . (int)($data['department_id'] ?? 0) . "',
            is_template = '" . (int)($data['is_template'] ?? 0) . "',
            escalation_enabled = '" . (int)($data['escalation_enabled'] ?? 0) . "',
            escalation_after_days = '" . (int)($data['escalation_after_days'] ?? 0) . "',
            notify_creator = '" . (int)($data['notify_creator'] ?? 1) . "',
            created_at = NOW()
        ");
        
        $workflow_id = $this->db->getLastId();
        
        // إنشاء العقد (Nodes)
        if (!empty($data['nodes'])) {
            foreach ($data['nodes'] as $node) {
                $this->createWorkflowNode($workflow_id, $node);
            }
        }
        
        // إنشاء الروابط (Connections)
        if (!empty($data['connections'])) {
            foreach ($data['connections'] as $connection) {
                $this->createWorkflowConnection($workflow_id, $connection);
            }
        }
        
        // تسجيل النشاط
        $this->logWorkflowActivity($workflow_id, 'created', 'تم إنشاء سير العمل');
        
        return $workflow_id;
    }
    
    /**
     * إنشاء عقدة في سير العمل
     */
    private function createWorkflowNode($workflow_id, $node_data) {
        $this->db->query("
            INSERT INTO cod_workflow_node SET 
            workflow_id = '" . (int)$workflow_id . "',
            node_id = '" . $this->db->escape($node_data['id']) . "',
            node_type = '" . $this->db->escape($node_data['type']) . "',
            node_name = '" . $this->db->escape($node_data['name']) . "',
            node_config = '" . $this->db->escape(json_encode($node_data['config'])) . "',
            position_x = '" . (int)$node_data['position']['x'] . "',
            position_y = '" . (int)$node_data['position']['y'] . "',
            created_at = NOW()
        ");
        
        return $this->db->getLastId();
    }
    
    /**
     * إنشاء رابط بين العقد
     */
    private function createWorkflowConnection($workflow_id, $connection_data) {
        $this->db->query("
            INSERT INTO cod_workflow_connection SET 
            workflow_id = '" . (int)$workflow_id . "',
            source_node_id = '" . $this->db->escape($connection_data['source']) . "',
            target_node_id = '" . $this->db->escape($connection_data['target']) . "',
            source_handle = '" . $this->db->escape($connection_data['sourceHandle']) . "',
            target_handle = '" . $this->db->escape($connection_data['targetHandle']) . "',
            condition_config = '" . $this->db->escape(json_encode($connection_data['condition'] ?? [])) . "',
            created_at = NOW()
        ");
        
        return $this->db->getLastId();
    }
    
    /**
     * تنفيذ سير العمل
     */
    public function executeWorkflow($workflow_id, $trigger_data = []) {
        // إنشاء مثيل تنفيذ جديد
        $execution_id = $this->createWorkflowExecution($workflow_id, $trigger_data);
        
        try {
            // الحصول على تعريف سير العمل
            $workflow = $this->getWorkflow($workflow_id);
            
            if (!$workflow || $workflow['status'] != 'active') {
                throw new Exception('سير العمل غير نشط أو غير موجود');
            }
            
            // البدء من العقدة المحفزة
            $start_node = $this->getStartNode($workflow_id);
            
            if (!$start_node) {
                throw new Exception('لا توجد عقدة بداية في سير العمل');
            }
            
            // تنفيذ العقد بالتسلسل
            $execution_context = [
                'workflow_id' => $workflow_id,
                'execution_id' => $execution_id,
                'trigger_data' => $trigger_data,
                'variables' => []
            ];
            
            $result = $this->executeNode($start_node, $execution_context);
            
            // تحديث حالة التنفيذ
            $this->updateExecutionStatus($execution_id, 'completed', $result);
            
            return $execution_id;
            
        } catch (Exception $e) {
            // تحديث حالة التنفيذ بالخطأ
            $this->updateExecutionStatus($execution_id, 'failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }
    
    /**
     * تنفيذ عقدة واحدة
     */
    private function executeNode($node, $context) {
        // تسجيل بداية تنفيذ العقدة
        $this->logNodeExecution($context['execution_id'], $node['node_id'], 'started');
        
        try {
            $result = null;
            
            switch ($node['node_type']) {
                case 'trigger':
                    $result = $this->executeTriggerNode($node, $context);
                    break;
                    
                case 'action':
                    $result = $this->executeActionNode($node, $context);
                    break;
                    
                case 'condition':
                    $result = $this->executeConditionNode($node, $context);
                    break;
                    
                case 'loop':
                    $result = $this->executeLoopNode($node, $context);
                    break;
                    
                case 'notification':
                    $result = $this->executeNotificationNode($node, $context);
                    break;
                    
                case 'approval':
                    $result = $this->executeApprovalNode($node, $context);
                    break;
                    
                case 'integration':
                    $result = $this->executeIntegrationNode($node, $context);
                    break;
                    
                default:
                    throw new Exception('نوع العقدة غير مدعوم: ' . $node['node_type']);
            }
            
            // تسجيل نجاح التنفيذ
            $this->logNodeExecution($context['execution_id'], $node['node_id'], 'completed', $result);
            
            // تنفيذ العقد التالية
            $next_nodes = $this->getNextNodes($node['node_id'], $context['workflow_id'], $result);
            
            foreach ($next_nodes as $next_node) {
                $this->executeNode($next_node, $context);
            }
            
            return $result;
            
        } catch (Exception $e) {
            // تسجيل فشل التنفيذ
            $this->logNodeExecution($context['execution_id'], $node['node_id'], 'failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }
    
    /**
     * تنفيذ عقدة الإجراء
     */
    private function executeActionNode($node, $context) {
        $config = json_decode($node['node_config'], true);
        $action_type = $config['action_type'];
        
        switch ($action_type) {
            case 'create_order':
                return $this->executeCreateOrderAction($config, $context);
                
            case 'send_email':
                return $this->executeSendEmailAction($config, $context);
                
            case 'update_inventory':
                return $this->executeUpdateInventoryAction($config, $context);
                
            case 'create_journal_entry':
                return $this->executeCreateJournalEntryAction($config, $context);
                
            case 'assign_task':
                return $this->executeAssignTaskAction($config, $context);
                
            default:
                throw new Exception('نوع الإجراء غير مدعوم: ' . $action_type);
        }
    }
    
    /**
     * تنفيذ عقدة الشرط
     */
    private function executeConditionNode($node, $context) {
        $config = json_decode($node['node_config'], true);
        
        $left_value = $this->evaluateExpression($config['left_operand'], $context);
        $right_value = $this->evaluateExpression($config['right_operand'], $context);
        $operator = $config['operator'];
        
        $result = false;
        
        switch ($operator) {
            case '==':
                $result = $left_value == $right_value;
                break;
            case '!=':
                $result = $left_value != $right_value;
                break;
            case '>':
                $result = $left_value > $right_value;
                break;
            case '<':
                $result = $left_value < $right_value;
                break;
            case '>=':
                $result = $left_value >= $right_value;
                break;
            case '<=':
                $result = $left_value <= $right_value;
                break;
            case 'contains':
                $result = strpos($left_value, $right_value) !== false;
                break;
            case 'in':
                $result = in_array($left_value, (array)$right_value);
                break;
        }
        
        return [
            'condition_result' => $result,
            'left_value' => $left_value,
            'right_value' => $right_value,
            'operator' => $operator
        ];
    }
    
    /**
     * تنفيذ عقدة الإشعار
     */
    private function executeNotificationNode($node, $context) {
        $config = json_decode($node['node_config'], true);
        
        $this->load->model('communication/unified_notification');
        
        $notification_data = [
            'title' => $this->evaluateExpression($config['title'], $context),
            'message' => $this->evaluateExpression($config['message'], $context),
            'type' => $config['notification_type'] ?? 'workflow',
            'priority' => $config['priority'] ?? 'medium',
            'reference_type' => 'workflow_execution',
            'reference_id' => $context['execution_id']
        ];
        
        // إرسال للمستخدمين المحددين
        if (!empty($config['recipients'])) {
            foreach ($config['recipients'] as $recipient) {
                $user_id = $this->evaluateExpression($recipient, $context);
                $notification_data['user_id'] = $user_id;
                $this->model_communication_unified_notification->addNotification($notification_data);
            }
        }
        
        return ['notifications_sent' => count($config['recipients'] ?? [])];
    }
    
    /**
     * تنفيذ عقدة الموافقة
     */
    private function executeApprovalNode($node, $context) {
        $config = json_decode($node['node_config'], true);
        
        // إنشاء طلب موافقة
        $approval_data = [
            'title' => $this->evaluateExpression($config['title'], $context),
            'description' => $this->evaluateExpression($config['description'], $context),
            'approver_id' => $this->evaluateExpression($config['approver_id'], $context),
            'workflow_execution_id' => $context['execution_id'],
            'node_id' => $node['node_id'],
            'approval_type' => $config['approval_type'] ?? 'single',
            'required_approvals' => $config['required_approvals'] ?? 1,
            'auto_approve_after_hours' => $config['auto_approve_after_hours'] ?? null
        ];
        
        $this->db->query("
            INSERT INTO cod_workflow_approval SET 
            workflow_execution_id = '" . (int)$context['execution_id'] . "',
            node_id = '" . $this->db->escape($node['node_id']) . "',
            title = '" . $this->db->escape($approval_data['title']) . "',
            description = '" . $this->db->escape($approval_data['description']) . "',
            approver_id = '" . (int)$approval_data['approver_id'] . "',
            approval_type = '" . $this->db->escape($approval_data['approval_type']) . "',
            required_approvals = '" . (int)$approval_data['required_approvals'] . "',
            status = 'pending',
            created_at = NOW()
        ");
        
        $approval_id = $this->db->getLastId();
        
        // إرسال إشعار للمعتمد
        $this->sendApprovalNotification($approval_id, $approval_data);
        
        // إيقاف تنفيذ سير العمل مؤقتاً حتى الموافقة
        $this->pauseWorkflowExecution($context['execution_id'], $node['node_id']);
        
        return ['approval_id' => $approval_id, 'status' => 'pending'];
    }
    
    /**
     * تقييم التعبيرات والمتغيرات
     */
    private function evaluateExpression($expression, $context) {
        if (is_string($expression) && strpos($expression, '{{') !== false) {
            // استبدال المتغيرات
            $expression = preg_replace_callback('/\{\{([^}]+)\}\}/', function($matches) use ($context) {
                $variable_path = trim($matches[1]);
                return $this->getVariableValue($variable_path, $context);
            }, $expression);
        }
        
        return $expression;
    }
    
    /**
     * الحصول على قيمة متغير
     */
    private function getVariableValue($path, $context) {
        $parts = explode('.', $path);
        $value = $context;
        
        foreach ($parts as $part) {
            if (isset($value[$part])) {
                $value = $value[$part];
            } else {
                return null;
            }
        }
        
        return $value;
    }
    
    /**
     * الحصول على العقد التالية
     */
    private function getNextNodes($current_node_id, $workflow_id, $execution_result = null) {
        $query = $this->db->query("
            SELECT wn.*, wc.condition_config 
            FROM cod_workflow_connection wc
            LEFT JOIN cod_workflow_node wn ON (wc.target_node_id = wn.node_id)
            WHERE wc.workflow_id = '" . (int)$workflow_id . "'
            AND wc.source_node_id = '" . $this->db->escape($current_node_id) . "'
        ");
        
        $next_nodes = [];
        
        foreach ($query->rows as $connection) {
            // تحقق من الشروط إذا وجدت
            if (!empty($connection['condition_config'])) {
                $condition = json_decode($connection['condition_config'], true);
                
                if (!$this->evaluateConnectionCondition($condition, $execution_result)) {
                    continue; // تخطي هذا الاتصال
                }
            }
            
            $next_nodes[] = $connection;
        }
        
        return $next_nodes;
    }
    
    /**
     * إنشاء مثيل تنفيذ سير العمل
     */
    private function createWorkflowExecution($workflow_id, $trigger_data) {
        $this->db->query("
            INSERT INTO cod_workflow_execution SET 
            workflow_id = '" . (int)$workflow_id . "',
            trigger_data = '" . $this->db->escape(json_encode($trigger_data)) . "',
            status = 'running',
            started_at = NOW(),
            started_by = '" . (int)$this->user->getId() . "'
        ");
        
        return $this->db->getLastId();
    }
    
    /**
     * تحديث حالة التنفيذ
     */
    private function updateExecutionStatus($execution_id, $status, $result = null) {
        $sql = "UPDATE cod_workflow_execution SET 
                status = '" . $this->db->escape($status) . "'";
        
        if ($result !== null) {
            $sql .= ", execution_result = '" . $this->db->escape(json_encode($result)) . "'";
        }
        
        if ($status == 'completed' || $status == 'failed') {
            $sql .= ", completed_at = NOW()";
        }
        
        $sql .= " WHERE execution_id = '" . (int)$execution_id . "'";
        
        $this->db->query($sql);
    }
    
    /**
     * تسجيل نشاط سير العمل
     */
    private function logWorkflowActivity($workflow_id, $action, $description) {
        $this->db->query("
            INSERT INTO cod_workflow_activity_log SET 
            workflow_id = '" . (int)$workflow_id . "',
            action = '" . $this->db->escape($action) . "',
            description = '" . $this->db->escape($description) . "',
            user_id = '" . (int)$this->user->getId() . "',
            created_at = NOW()
        ");
    }
    
    /**
     * تسجيل تنفيذ العقدة
     */
    private function logNodeExecution($execution_id, $node_id, $status, $result = null) {
        $this->db->query("
            INSERT INTO cod_workflow_node_execution SET 
            execution_id = '" . (int)$execution_id . "',
            node_id = '" . $this->db->escape($node_id) . "',
            status = '" . $this->db->escape($status) . "',
            execution_result = '" . $this->db->escape(json_encode($result)) . "',
            executed_at = NOW()
        ");
    }
    
    /**
     * الحصول على سير العمل
     */
    public function getWorkflow($workflow_id) {
        $query = $this->db->query("
            SELECT * FROM cod_unified_workflow 
            WHERE workflow_id = '" . (int)$workflow_id . "'
        ");
        
        return $query->num_rows ? $query->row : false;
    }
    
    /**
     * الحصول على العقدة الأولى
     */
    private function getStartNode($workflow_id) {
        $query = $this->db->query("
            SELECT * FROM cod_workflow_node 
            WHERE workflow_id = '" . (int)$workflow_id . "'
            AND node_type = 'trigger'
            ORDER BY created_at ASC
            LIMIT 1
        ");
        
        return $query->num_rows ? $query->row : false;
    }
}
