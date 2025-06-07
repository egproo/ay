<?php
/**
 * Workflow Model
 */
class ModelWorkflowWorkflow extends Model {
    public function addWorkflow($data) {
        $this->db->query("INSERT INTO " . DB_PREFIX . "workflow SET 
            name = '" . $this->db->escape($data['name']) . "', 
            description = '" . $this->db->escape($data['description']) . "', 
            workflow_data = '" . $this->db->escape(isset($data['workflow_data']) ? $data['workflow_data'] : '{}') . "', 
            status = '" . (int)$data['status'] . "', 
            date_added = NOW(), 
            date_modified = NOW()");

        $workflow_id = $this->db->getLastId();

        return $workflow_id;
    }

    public function editWorkflow($workflow_id, $data) {
        $this->db->query("UPDATE " . DB_PREFIX . "workflow SET 
            name = '" . $this->db->escape($data['name']) . "', 
            description = '" . $this->db->escape($data['description']) . "', 
            workflow_data = '" . $this->db->escape(isset($data['workflow_data']) ? $data['workflow_data'] : '{}') . "', 
            status = '" . (int)$data['status'] . "', 
            date_modified = NOW() 
            WHERE workflow_id = '" . (int)$workflow_id . "'");
    }

    public function saveWorkflow($data) {
        if (isset($data['workflow_id']) && $data['workflow_id']) {
            $this->editWorkflow($data['workflow_id'], $data);
            return $data['workflow_id'];
        } else {
            return $this->addWorkflow($data);
        }
    }

    public function deleteWorkflow($workflow_id) {
        $this->db->query("DELETE FROM " . DB_PREFIX . "workflow WHERE workflow_id = '" . (int)$workflow_id . "'");
        $this->db->query("DELETE FROM " . DB_PREFIX . "workflow_instance WHERE workflow_id = '" . (int)$workflow_id . "'");
    }

    public function getWorkflow($workflow_id) {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "workflow WHERE workflow_id = '" . (int)$workflow_id . "'");

        return $query->row;
    }

    public function getWorkflows($data = array()) {
        $sql = "SELECT * FROM " . DB_PREFIX . "workflow";

        $sort_data = array(
            'name',
            'status',
            'date_added'
        );

        if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
            $sql .= " ORDER BY " . $data['sort'];
        } else {
            $sql .= " ORDER BY name";
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

    public function getTotalWorkflows() {
        $query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "workflow");

        return $query->row['total'];
    }

    // Workflow instance methods
    public function createWorkflowInstance($workflow_id, $reference_id, $reference_type, $data = array()) {
        $this->db->query("INSERT INTO " . DB_PREFIX . "workflow_instance SET 
            workflow_id = '" . (int)$workflow_id . "', 
            reference_id = '" . (int)$reference_id . "', 
            reference_type = '" . $this->db->escape($reference_type) . "', 
            status = 'active', 
            current_step = '" . $this->db->escape(isset($data['current_step']) ? $data['current_step'] : 'start') . "', 
            instance_data = '" . $this->db->escape(isset($data['instance_data']) ? json_encode($data['instance_data']) : '') . "', 
            created_by = '" . (int)$this->user->getId() . "', 
            date_created = NOW(), 
            date_modified = NOW()");

        return $this->db->getLastId();
    }

    public function updateWorkflowInstanceStatus($instance_id, $status, $data = array()) {
        $this->db->query("UPDATE " . DB_PREFIX . "workflow_instance SET 
            status = '" . $this->db->escape($status) . "', 
            current_step = '" . $this->db->escape(isset($data['current_step']) ? $data['current_step'] : '') . "', 
            instance_data = '" . $this->db->escape(isset($data['instance_data']) ? json_encode($data['instance_data']) : '') . "', 
            date_modified = NOW() 
            WHERE instance_id = '" . (int)$instance_id . "'");
    }

    public function getWorkflowInstance($instance_id) {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "workflow_instance WHERE instance_id = '" . (int)$instance_id . "'");

        $instance = $query->row;

        if (isset($instance['instance_data']) && $instance['instance_data']) {
            $instance['instance_data'] = json_decode($instance['instance_data'], true);
        }

        return $instance;
    }

    public function getWorkflowInstancesByReference($reference_id, $reference_type) {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "workflow_instance WHERE reference_id = '" . (int)$reference_id . "' AND reference_type = '" . $this->db->escape($reference_type) . "'");

        $instances = array();

        foreach ($query->rows as $instance) {
            if (isset($instance['instance_data']) && $instance['instance_data']) {
                $instance['instance_data'] = json_decode($instance['instance_data'], true);
            }
            
            $instances[] = $instance;
        }

        return $instances;
    }

    public function getActiveWorkflowInstances() {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "workflow_instance WHERE status = 'active' ORDER BY date_modified DESC");

        $instances = array();

        foreach ($query->rows as $instance) {
            if (isset($instance['instance_data']) && $instance['instance_data']) {
                $instance['instance_data'] = json_decode($instance['instance_data'], true);
            }
            
            $instances[] = $instance;
        }

        return $instances;
    }

    // Workflow execution methods
    public function executeWorkflowStep($instance_id, $action, $data = array()) {
        $instance = $this->getWorkflowInstance($instance_id);
        
        if (!$instance) {
            return false;
        }
        
        $workflow = $this->getWorkflow($instance['workflow_id']);
        
        if (!$workflow || empty($workflow['workflow_data'])) {
            return false;
        }
        
        $workflow_data = json_decode($workflow['workflow_data'], true);
        
        if (empty($workflow_data['nodes']) || empty($workflow_data['connections'])) {
            return false;
        }
        
        // Find current node
        $current_node = null;
        foreach ($workflow_data['nodes'] as $node) {
            if ($node['id'] === $instance['current_step']) {
                $current_node = $node;
                break;
            }
        }
        
        if (!$current_node) {
            return false;
        }
        
        // Find connections from current node
        $outgoing_connections = array();
        foreach ($workflow_data['connections'] as $connection) {
            if ($connection['sourceId'] === $current_node['id']) {
                $outgoing_connections[] = $connection;
            }
        }
        
        // Determine next node based on action
        $next_connection = null;
        foreach ($outgoing_connections as $connection) {
            if (isset($connection['condition'])) {
                // Complex condition evaluation (simplified)
                if ($connection['condition'] === $action) {
                    $next_connection = $connection;
                    break;
                }
            } else {
                // Default connection if no condition
                $next_connection = $connection;
            }
        }
        
        if (!$next_connection) {
            return false;
        }
        
        // Find target node
        $next_node = null;
        foreach ($workflow_data['nodes'] as $node) {
            if ($node['id'] === $next_connection['targetId']) {
                $next_node = $node;
                break;
            }
        }
        
        if (!$next_node) {
            return false;
        }
        
        // Execute node actions based on type
        switch ($next_node['type']) {
            case 'end':
                $this->updateWorkflowInstanceStatus($instance_id, 'completed', array(
                    'current_step' => $next_node['id'],
                    'instance_data' => $instance['instance_data']
                ));
                break;
                
            default:
                $this->updateWorkflowInstanceStatus($instance_id, 'active', array(
                    'current_step' => $next_node['id'],
                    'instance_data' => $instance['instance_data']
                ));
                break;
        }
        
        return array(
            'next_node' => $next_node,
            'status' => $next_node['type'] === 'end' ? 'completed' : 'active'
        );
    }

    // Task execution methods
    public function executeWorkflowTask($instance_id, $task_data = array()) {
        $instance = $this->getWorkflowInstance($instance_id);
        
        if (!$instance) {
            return false;
        }
        
        $workflow = $this->getWorkflow($instance['workflow_id']);
        $current_step = $instance['current_step'];
        
        // Find current node in the workflow data
        $workflow_data = json_decode($workflow['workflow_data'], true);
        $current_node = null;
        
        foreach ($workflow_data['nodes'] as $node) {
            if ($node['id'] === $current_step) {
                $current_node = $node;
                break;
            }
        }
        
        if (!$current_node) {
            return false;
        }
        
        // Execute based on node type
        switch ($current_node['type']) {
            case 'email':
                return $this->executeEmailNode($instance_id, $current_node, $instance);
                
            case 'task':
                return $this->executeTaskNode($instance_id, $current_node, $instance, $task_data);
                
            case 'delay':
                return $this->executeDelayNode($instance_id, $current_node, $instance);
                
            case 'decision':
                $decision = isset($task_data['decision']) ? $task_data['decision'] : 'default';
                return $this->executeDecisionNode($instance_id, $current_node, $instance, $decision);
                
            default:
                // For other node types, simply advance to the next step
                return $this->advanceToNextStep($instance_id, $current_node, $instance);
        }
    }
    
    protected function executeEmailNode($instance_id, $node, $instance) {
        $this->load->model('mail/mail');
        
        // Get reference data
        $reference_data = $this->getReferenceData($instance['reference_id'], $instance['reference_type']);
        
        // Get email parameters from node
        $template = isset($node['template']) ? $node['template'] : 'notification';
        $recipients = $this->getEmailRecipients($node, $instance, $reference_data);
        
        // Send email
        foreach ($recipients as $recipient) {
            $this->model_mail_mail->sendTemplateMail(
                $recipient['email'],
                $template,
                $reference_data
            );
        }
        
        // Log the action
        $this->addWorkflowHistory($instance_id, 'email_sent', [
            'node_id' => $node['id'],
            'recipients' => $recipients
        ]);
        
        // Move to next step
        return $this->advanceToNextStep($instance_id, $node, $instance);
    }
    
    protected function executeTaskNode($instance_id, $node, $instance, $task_data) {
        // Assign task to users
        $assignee = isset($node['assign']) ? $node['assign'] : 'user';
        $assignee_id = isset($node['assignee_id']) ? $node['assignee_id'] : 0;
        
        // Create a task record
        $this->load->model('user/user');
        $task_id = $this->createWorkflowTask($instance_id, $node, $assignee, $assignee_id);
        
        // Send notification to assignee
        if ($assignee === 'user' && $assignee_id > 0) {
            $user_info = $this->model_user_user->getUser($assignee_id);
            if ($user_info && $user_info['email']) {
                $this->sendTaskNotification($user_info['email'], $node, $instance);
            }
        } else if ($assignee === 'role' && $assignee_id > 0) {
            $this->sendRoleNotification($assignee_id, $node, $instance);
        }
        
        // Log the action
        $this->addWorkflowHistory($instance_id, 'task_assigned', [
            'node_id' => $node['id'],
            'assignee' => $assignee,
            'assignee_id' => $assignee_id,
            'task_id' => $task_id
        ]);
        
        // If the task is marked as completed already, move to next step
        if (isset($task_data['status']) && $task_data['status'] === 'completed') {
            return $this->advanceToNextStep($instance_id, $node, $instance);
        }
        
        // Otherwise, update the instance but stay on the current step
        $this->updateWorkflowInstanceStatus($instance_id, 'active', [
            'current_step' => $node['id'],
            'instance_data' => array_merge($instance['instance_data'] ?: [], ['last_task_id' => $task_id])
        ]);
        
        return true;
    }
    
    protected function executeDelayNode($instance_id, $node, $instance) {
        // Get delay duration from node
        $delay = isset($node['delay']) ? (int)$node['delay'] : 24;
        
        // Calculate wake-up time
        $wake_time = date('Y-m-d H:i:s', strtotime('+' . $delay . ' hours'));
        
        // Schedule the task
        $this->scheduleWorkflowWakeup($instance_id, $wake_time);
        
        // Log the action
        $this->addWorkflowHistory($instance_id, 'delay_started', [
            'node_id' => $node['id'],
            'delay_hours' => $delay,
            'wake_time' => $wake_time
        ]);
        
        // Update instance status
        $this->updateWorkflowInstanceStatus($instance_id, 'waiting', [
            'current_step' => $node['id'],
            'instance_data' => array_merge($instance['instance_data'] ?: [], ['wake_time' => $wake_time])
        ]);
        
        return true;
    }
    
    protected function executeDecisionNode($instance_id, $node, $instance, $decision) {
        // Find appropriate connection based on decision
        $workflow_data = json_decode($this->getWorkflow($instance['workflow_id'])['workflow_data'], true);
        $next_connection = null;
        
        foreach ($workflow_data['connections'] as $connection) {
            if ($connection['sourceId'] === $node['id']) {
                if (isset($connection['condition']) && $connection['condition'] === $decision) {
                    $next_connection = $connection;
                    break;
                } else if (!isset($connection['condition']) && $decision === 'default') {
                    $next_connection = $connection;
                }
            }
        }
        
        if (!$next_connection) {
            return false;
        }
        
        // Find target node
        $next_node = null;
        foreach ($workflow_data['nodes'] as $node_data) {
            if ($node_data['id'] === $next_connection['targetId']) {
                $next_node = $node_data;
                break;
            }
        }
        
        if (!$next_node) {
            return false;
        }
        
        // Log the decision
        $this->addWorkflowHistory($instance_id, 'decision_made', [
            'node_id' => $node['id'],
            'decision' => $decision,
            'next_node' => $next_node['id']
        ]);
        
        // Update instance to next step
        $this->updateWorkflowInstanceStatus($instance_id, 'active', [
            'current_step' => $next_node['id'],
            'instance_data' => $instance['instance_data']
        ]);
        
        // Execute the next node
        return $this->executeWorkflowTask($instance_id);
    }
    
    protected function advanceToNextStep($instance_id, $current_node, $instance) {
        $workflow_data = json_decode($this->getWorkflow($instance['workflow_id'])['workflow_data'], true);
        
        // Find the next connection
        $next_connection = null;
        foreach ($workflow_data['connections'] as $connection) {
            if ($connection['sourceId'] === $current_node['id']) {
                $next_connection = $connection;
                break;
            }
        }
        
        if (!$next_connection) {
            // If no next connection, check if this is an end node
            if ($current_node['type'] === 'end') {
                $this->updateWorkflowInstanceStatus($instance_id, 'completed', [
                    'current_step' => $current_node['id']
                ]);
                
                $this->addWorkflowHistory($instance_id, 'workflow_completed', [
                    'node_id' => $current_node['id']
                ]);
                
                return true;
            }
            
            return false;
        }
        
        // Find the next node
        $next_node = null;
        foreach ($workflow_data['nodes'] as $node) {
            if ($node['id'] === $next_connection['targetId']) {
                $next_node = $node;
                break;
            }
        }
        
        if (!$next_node) {
            return false;
        }
        
        // Update instance to next step
        $this->updateWorkflowInstanceStatus($instance_id, 'active', [
            'current_step' => $next_node['id'],
            'instance_data' => $instance['instance_data']
        ]);
        
        $this->addWorkflowHistory($instance_id, 'step_advanced', [
            'from_node' => $current_node['id'],
            'to_node' => $next_node['id']
        ]);
        
        // Execute the next node
        return $this->executeWorkflowTask($instance_id);
    }
    
    // Helper methods
    protected function getReferenceData($reference_id, $reference_type) {
        // Load reference data based on type
        switch ($reference_type) {
            case 'order':
                $this->load->model('sale/order');
                return $this->model_sale_order->getOrder($reference_id);
                
            case 'customer':
                $this->load->model('customer/customer');
                return $this->model_customer_customer->getCustomer($reference_id);
                
            case 'product':
                $this->load->model('catalog/product');
                return $this->model_catalog_product->getProduct($reference_id);
                
            case 'purchase':
                $this->load->model('purchase/purchase');
                return $this->model_purchase_purchase->getPurchase($reference_id);
                
            default:
                return [];
        }
    }
    
    protected function getEmailRecipients($node, $instance, $reference_data) {
        $recipients = [];
        
        // Add specific recipients based on node configuration
        if (isset($node['recipient_type'])) {
            switch ($node['recipient_type']) {
                case 'user':
                    if (isset($node['recipient_id'])) {
                        $this->load->model('user/user');
                        $user_info = $this->model_user_user->getUser($node['recipient_id']);
                        if ($user_info && $user_info['email']) {
                            $recipients[] = [
                                'email' => $user_info['email'],
                                'name' => $user_info['firstname'] . ' ' . $user_info['lastname']
                            ];
                        }
                    }
                    break;
                    
                case 'role':
                    if (isset($node['recipient_id'])) {
                        $this->load->model('user/user');
                        $users = $this->model_user_user->getUsersByGroupId($node['recipient_id']);
                        foreach ($users as $user) {
                            if ($user['email']) {
                                $recipients[] = [
                                    'email' => $user['email'],
                                    'name' => $user['firstname'] . ' ' . $user['lastname']
                                ];
                            }
                        }
                    }
                    break;
                    
                case 'customer':
                    if (isset($reference_data['email'])) {
                        $recipients[] = [
                            'email' => $reference_data['email'],
                            'name' => $reference_data['firstname'] . ' ' . $reference_data['lastname']
                        ];
                    }
                    break;
            }
        }
        
        return $recipients;
    }
    
    protected function sendTaskNotification($email, $node, $instance) {
        $this->load->model('mail/mail');
        
        // Get reference data
        $reference_data = $this->getReferenceData($instance['reference_id'], $instance['reference_type']);
        
        // Send task notification
        $this->model_mail_mail->sendTemplateMail(
            $email,
            'workflow_task',
            array_merge($reference_data, [
                'task_name' => $node['label'],
                'task_description' => isset($node['description']) ? $node['description'] : '',
                'workflow_name' => $this->getWorkflow($instance['workflow_id'])['name'],
                'due_days' => isset($node['dueDays']) ? $node['dueDays'] : '1'
            ])
        );
        
        return true;
    }
    
    protected function sendRoleNotification($role_id, $node, $instance) {
        $this->load->model('user/user');
        $this->load->model('mail/mail');
        
        // Get users with this role
        $users = $this->model_user_user->getUsersByGroupId($role_id);
        
        // Get reference data
        $reference_data = $this->getReferenceData($instance['reference_id'], $instance['reference_type']);
        
        // Send notification to each user
        foreach ($users as $user) {
            if ($user['email']) {
                $this->model_mail_mail->sendTemplateMail(
                    $user['email'],
                    'workflow_task',
                    array_merge($reference_data, [
                        'task_name' => $node['label'],
                        'task_description' => isset($node['description']) ? $node['description'] : '',
                        'workflow_name' => $this->getWorkflow($instance['workflow_id'])['name'],
                        'due_days' => isset($node['dueDays']) ? $node['dueDays'] : '1'
                    ])
                );
            }
        }
        
        return true;
    }
    
    protected function createWorkflowTask($instance_id, $node, $assignee, $assignee_id) {
        // Create a task record in the system
        $this->db->query("INSERT INTO " . DB_PREFIX . "workflow_task SET 
            instance_id = '" . (int)$instance_id . "',
            node_id = '" . $this->db->escape($node['id']) . "',
            assignee_type = '" . $this->db->escape($assignee) . "',
            assignee_id = '" . (int)$assignee_id . "',
            name = '" . $this->db->escape($node['label']) . "',
            description = '" . $this->db->escape(isset($node['description']) ? $node['description'] : '') . "',
            status = 'pending',
            due_date = '" . $this->db->escape(date('Y-m-d H:i:s', strtotime('+' . (isset($node['dueDays']) ? (int)$node['dueDays'] : 1) . ' days'))) . "',
            date_added = NOW(),
            date_modified = NOW()");
            
        return $this->db->getLastId();
    }
    
    protected function scheduleWorkflowWakeup($instance_id, $wake_time) {
        // Add to workflow schedule table
        $this->db->query("INSERT INTO " . DB_PREFIX . "workflow_schedule SET 
            instance_id = '" . (int)$instance_id . "',
            wake_time = '" . $this->db->escape($wake_time) . "',
            status = 'scheduled',
            date_added = NOW()");
            
        return $this->db->getLastId();
    }
    
    protected function addWorkflowHistory($instance_id, $action, $data = []) {
        $this->db->query("INSERT INTO " . DB_PREFIX . "workflow_history SET 
            instance_id = '" . (int)$instance_id . "',
            user_id = '" . (int)$this->user->getId() . "',
            node_id = '" . $this->db->escape(isset($data['node_id']) ? $data['node_id'] : '') . "',
            action = '" . $this->db->escape($action) . "',
            data = '" . $this->db->escape(json_encode($data)) . "',
            date_added = NOW()");
            
        return $this->db->getLastId();
    }
    
    // Workflow task management methods
    public function getWorkflowTasks($data = []) {
        $sql = "SELECT wt.*, wi.reference_id, wi.reference_type, w.name AS workflow_name 
                FROM " . DB_PREFIX . "workflow_task wt 
                LEFT JOIN " . DB_PREFIX . "workflow_instance wi ON (wt.instance_id = wi.instance_id)
                LEFT JOIN " . DB_PREFIX . "workflow w ON (wi.workflow_id = w.workflow_id)
                WHERE 1=1";
        
        if (isset($data['filter_status'])) {
            $sql .= " AND wt.status = '" . $this->db->escape($data['filter_status']) . "'";
        }
        
        if (isset($data['filter_assignee_type']) && isset($data['filter_assignee_id'])) {
            $sql .= " AND wt.assignee_type = '" . $this->db->escape($data['filter_assignee_type']) . "'";
            $sql .= " AND wt.assignee_id = '" . (int)$data['filter_assignee_id'] . "'";
        }
        
        if (isset($data['filter_reference_type'])) {
            $sql .= " AND wi.reference_type = '" . $this->db->escape($data['filter_reference_type']) . "'";
        }
        
        if (isset($data['filter_reference_id'])) {
            $sql .= " AND wi.reference_id = '" . (int)$data['filter_reference_id'] . "'";
        }
        
        $sort_data = [
            'wt.name',
            'workflow_name',
            'wt.status',
            'wt.due_date',
            'wt.date_added'
        ];
        
        if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
            $sql .= " ORDER BY " . $data['sort'];
        } else {
            $sql .= " ORDER BY wt.due_date";
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
    
    public function getWorkflowTask($task_id) {
        $query = $this->db->query("SELECT wt.*, wi.reference_id, wi.reference_type, w.name AS workflow_name 
                FROM " . DB_PREFIX . "workflow_task wt 
                LEFT JOIN " . DB_PREFIX . "workflow_instance wi ON (wt.instance_id = wi.instance_id)
                LEFT JOIN " . DB_PREFIX . "workflow w ON (wi.workflow_id = w.workflow_id)
                WHERE wt.task_id = '" . (int)$task_id . "'");
        
        return $query->row;
    }
    
    public function updateWorkflowTaskStatus($task_id, $status, $comment = '') {
        $this->db->query("UPDATE " . DB_PREFIX . "workflow_task SET 
            status = '" . $this->db->escape($status) . "',
            comment = '" . $this->db->escape($comment) . "',
            date_modified = NOW() 
            WHERE task_id = '" . (int)$task_id . "'");
            
        // If task is completed or rejected, advance workflow
        if ($status == 'completed' || $status == 'rejected') {
            $task_info = $this->getWorkflowTask($task_id);
            
            if ($task_info) {
                $this->executeWorkflowTask($task_info['instance_id'], [
                    'status' => $status,
                    'comment' => $comment,
                    'decision' => $status == 'completed' ? 'approved' : 'rejected'
                ]);
            }
        }
    }
    
    // Scheduler methods
    public function processScheduledWorkflows() {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "workflow_schedule 
                WHERE status = 'scheduled' AND wake_time <= NOW()");
                
        foreach ($query->rows as $schedule) {
            // Update schedule status
            $this->db->query("UPDATE " . DB_PREFIX . "workflow_schedule SET 
                status = 'processing',
                date_modified = NOW() 
                WHERE schedule_id = '" . (int)$schedule['schedule_id'] . "'");
                
            // Execute the workflow
            $result = $this->executeWorkflowTask($schedule['instance_id']);
            
            // Update schedule status
            $this->db->query("UPDATE " . DB_PREFIX . "workflow_schedule SET 
                status = 'completed',
                date_modified = NOW() 
                WHERE schedule_id = '" . (int)$schedule['schedule_id'] . "'");
        }
    }
} 