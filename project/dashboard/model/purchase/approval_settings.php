<?php
/**
 * AYM ERP - Purchase Approval Settings Model
 *
 * @author AYM ERP Development Team
 * @copyright 2024 AYM ERP
 * @license Commercial License
 * @version 1.0.0
 * @link https://aym-erp.com
 */

class ModelPurchaseApprovalSettings extends Model {

    public function editSettings($data) {
        $this->db->query("DELETE FROM " . DB_PREFIX . "setting WHERE store_id = '0' AND `code` = 'purchase_approval'");

        foreach ($data as $key => $value) {
            if (substr($key, 0, 17) == 'purchase_approval') {
                if (!is_array($value)) {
                    $this->db->query("INSERT INTO " . DB_PREFIX . "setting SET store_id = '0', `code` = 'purchase_approval', `key` = '" . $this->db->escape($key) . "', `value` = '" . $this->db->escape($value) . "'");
                } else {
                    $this->db->query("INSERT INTO " . DB_PREFIX . "setting SET store_id = '0', `code` = 'purchase_approval', `key` = '" . $this->db->escape($key) . "', `value` = '" . $this->db->escape(json_encode($value, true)) . "', serialized = '1'");
                }
            }
        }

        // Save amount thresholds
        if (isset($data['amount_thresholds'])) {
            $this->saveAmountThresholds($data['amount_thresholds']);
        }

        // Save department rules
        if (isset($data['department_rules'])) {
            $this->saveDepartmentRules($data['department_rules']);
        }

        // Save category rules
        if (isset($data['category_rules'])) {
            $this->saveCategoryRules($data['category_rules']);
        }

        // Clear cache
        $this->cache->delete('purchase_approval_settings');
    }

    public function getSettings() {
        $settings = array();

        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "setting WHERE store_id = '0' AND `code` = 'purchase_approval'");

        foreach ($query->rows as $result) {
            if (!$result['serialized']) {
                $settings[str_replace('purchase_approval_', '', $result['key'])] = $result['value'];
            } else {
                $settings[str_replace('purchase_approval_', '', $result['key'])] = json_decode($result['value'], true);
            }
        }

        return $settings;
    }

    public function saveAmountThresholds($thresholds) {
        $this->db->query("DELETE FROM " . DB_PREFIX . "purchase_approval_threshold");

        foreach ($thresholds as $threshold) {
            if (!empty($threshold['amount']) && !empty($threshold['approver_type']) && !empty($threshold['approver_id'])) {
                $this->db->query("INSERT INTO " . DB_PREFIX . "purchase_approval_threshold SET
                    amount = '" . (float)$threshold['amount'] . "',
                    currency_id = '" . (int)($threshold['currency_id'] ?? 1) . "',
                    approver_type = '" . $this->db->escape($threshold['approver_type']) . "',
                    approver_id = '" . (int)$threshold['approver_id'] . "',
                    department_id = '" . (int)($threshold['department_id'] ?? 0) . "',
                    category_id = '" . (int)($threshold['category_id'] ?? 0) . "',
                    sort_order = '" . (int)($threshold['sort_order'] ?? 0) . "',
                    status = '" . (int)($threshold['status'] ?? 1) . "'");
            }
        }
    }

    public function getAmountThresholds() {
        $query = $this->db->query("SELECT pat.*,
            CASE
                WHEN pat.approver_type = 'user' THEN CONCAT(u.firstname, ' ', u.lastname)
                WHEN pat.approver_type = 'group' THEN ug.name
                ELSE 'Unknown'
            END as approver_name,
            c.title as currency_title,
            cat.name as category_name
            FROM " . DB_PREFIX . "purchase_approval_threshold pat
            LEFT JOIN " . DB_PREFIX . "user u ON (pat.approver_type = 'user' AND pat.approver_id = u.user_id)
            LEFT JOIN " . DB_PREFIX . "user_group ug ON (pat.approver_type = 'group' AND pat.approver_id = ug.user_group_id)
            LEFT JOIN " . DB_PREFIX . "currency c ON (pat.currency_id = c.currency_id)
            LEFT JOIN " . DB_PREFIX . "category cat ON (pat.category_id = cat.category_id)
            WHERE pat.status = '1'
            ORDER BY pat.sort_order ASC, pat.amount ASC");

        return $query->rows;
    }

    public function saveDepartmentRules($rules) {
        $this->db->query("DELETE FROM " . DB_PREFIX . "purchase_approval_department_rule");

        foreach ($rules as $rule) {
            if (!empty($rule['department_id']) && !empty($rule['approver_type']) && !empty($rule['approver_id'])) {
                $this->db->query("INSERT INTO " . DB_PREFIX . "purchase_approval_department_rule SET
                    department_id = '" . (int)$rule['department_id'] . "',
                    approver_type = '" . $this->db->escape($rule['approver_type']) . "',
                    approver_id = '" . (int)$rule['approver_id'] . "',
                    min_amount = '" . (float)($rule['min_amount'] ?? 0) . "',
                    max_amount = '" . (float)($rule['max_amount'] ?? 0) . "',
                    sort_order = '" . (int)($rule['sort_order'] ?? 0) . "',
                    status = '" . (int)($rule['status'] ?? 1) . "'");
            }
        }
    }

    public function getDepartmentRules() {
        $query = $this->db->query("SELECT padr.*,
            CASE
                WHEN padr.approver_type = 'user' THEN CONCAT(u.firstname, ' ', u.lastname)
                WHEN padr.approver_type = 'group' THEN ug.name
                ELSE 'Unknown'
            END as approver_name
            FROM " . DB_PREFIX . "purchase_approval_department_rule padr
            LEFT JOIN " . DB_PREFIX . "user u ON (padr.approver_type = 'user' AND padr.approver_id = u.user_id)
            LEFT JOIN " . DB_PREFIX . "user_group ug ON (padr.approver_type = 'group' AND padr.approver_id = ug.user_group_id)
            WHERE padr.status = '1'
            ORDER BY padr.department_id ASC, padr.sort_order ASC");

        return $query->rows;
    }

    public function saveCategoryRules($rules) {
        $this->db->query("DELETE FROM " . DB_PREFIX . "purchase_approval_category_rule");

        foreach ($rules as $rule) {
            if (!empty($rule['category_id']) && !empty($rule['approver_type']) && !empty($rule['approver_id'])) {
                $this->db->query("INSERT INTO " . DB_PREFIX . "purchase_approval_category_rule SET
                    category_id = '" . (int)$rule['category_id'] . "',
                    approver_type = '" . $this->db->escape($rule['approver_type']) . "',
                    approver_id = '" . (int)$rule['approver_id'] . "',
                    min_amount = '" . (float)($rule['min_amount'] ?? 0) . "',
                    max_amount = '" . (float)($rule['max_amount'] ?? 0) . "',
                    sort_order = '" . (int)($rule['sort_order'] ?? 0) . "',
                    status = '" . (int)($rule['status'] ?? 1) . "'");
            }
        }
    }

    public function getCategoryRules() {
        $query = $this->db->query("SELECT pacr.*,
            CASE
                WHEN pacr.approver_type = 'user' THEN CONCAT(u.firstname, ' ', u.lastname)
                WHEN pacr.approver_type = 'group' THEN ug.name
                ELSE 'Unknown'
            END as approver_name,
            c.name as category_name
            FROM " . DB_PREFIX . "purchase_approval_category_rule pacr
            LEFT JOIN " . DB_PREFIX . "user u ON (pacr.approver_type = 'user' AND pacr.approver_id = u.user_id)
            LEFT JOIN " . DB_PREFIX . "user_group ug ON (pacr.approver_type = 'group' AND pacr.approver_id = ug.user_group_id)
            LEFT JOIN " . DB_PREFIX . "category c ON (pacr.category_id = c.category_id)
            WHERE pacr.status = '1'
            ORDER BY pacr.category_id ASC, pacr.sort_order ASC");

        return $query->rows;
    }

    public function saveWorkflow($data) {
        $this->db->query("DELETE FROM " . DB_PREFIX . "purchase_approval_workflow_step");

        if (isset($data['workflow_steps'])) {
            foreach ($data['workflow_steps'] as $step) {
                if (!empty($step['step_name']) && !empty($step['approver_type']) && !empty($step['approver_id'])) {
                    $this->db->query("INSERT INTO " . DB_PREFIX . "purchase_approval_workflow_step SET
                        step_name = '" . $this->db->escape($step['step_name']) . "',
                        description = '" . $this->db->escape($step['description'] ?? '') . "',
                        approver_type = '" . $this->db->escape($step['approver_type']) . "',
                        approver_id = '" . (int)$step['approver_id'] . "',
                        conditions = '" . $this->db->escape(json_encode($step['conditions'] ?? array())) . "',
                        sort_order = '" . (int)($step['sort_order'] ?? 0) . "',
                        is_required = '" . (int)($step['is_required'] ?? 1) . "',
                        timeout_hours = '" . (int)($step['timeout_hours'] ?? 24) . "',
                        escalation_enabled = '" . (int)($step['escalation_enabled'] ?? 0) . "',
                        escalation_approver_type = '" . $this->db->escape($step['escalation_approver_type'] ?? '') . "',
                        escalation_approver_id = '" . (int)($step['escalation_approver_id'] ?? 0) . "',
                        status = '" . (int)($step['status'] ?? 1) . "'");
                }
            }
        }
    }

    public function getWorkflowSteps() {
        $query = $this->db->query("SELECT paws.*,
            CASE
                WHEN paws.approver_type = 'user' THEN CONCAT(u.firstname, ' ', u.lastname)
                WHEN paws.approver_type = 'group' THEN ug.name
                ELSE 'Unknown'
            END as approver_name,
            CASE
                WHEN paws.escalation_approver_type = 'user' THEN CONCAT(eu.firstname, ' ', eu.lastname)
                WHEN paws.escalation_approver_type = 'group' THEN eug.name
                ELSE ''
            END as escalation_approver_name
            FROM " . DB_PREFIX . "purchase_approval_workflow_step paws
            LEFT JOIN " . DB_PREFIX . "user u ON (paws.approver_type = 'user' AND paws.approver_id = u.user_id)
            LEFT JOIN " . DB_PREFIX . "user_group ug ON (paws.approver_type = 'group' AND paws.approver_id = ug.user_group_id)
            LEFT JOIN " . DB_PREFIX . "user eu ON (paws.escalation_approver_type = 'user' AND paws.escalation_approver_id = eu.user_id)
            LEFT JOIN " . DB_PREFIX . "user_group eug ON (paws.escalation_approver_type = 'group' AND paws.escalation_approver_id = eug.user_group_id)
            WHERE paws.status = '1'
            ORDER BY paws.sort_order ASC");

        return $query->rows;
    }

    public function getApprovalFlow($data) {
        $approval_flow = array();

        $amount = $data['amount'];
        $department_id = $data['department_id'] ?? 0;
        $category_id = $data['category_id'] ?? 0;
        $user_id = $data['user_id'] ?? 0;

        // Get amount-based thresholds
        $amount_approvers = $this->getAmountBasedApprovers($amount, $department_id, $category_id);

        // Get department-based approvers
        $department_approvers = $this->getDepartmentBasedApprovers($department_id, $amount);

        // Get category-based approvers
        $category_approvers = $this->getCategoryBasedApprovers($category_id, $amount);

        // Get workflow steps
        $workflow_steps = $this->getWorkflowSteps();

        // Combine and deduplicate approvers
        $all_approvers = array_merge($amount_approvers, $department_approvers, $category_approvers);

        // Add workflow steps
        foreach ($workflow_steps as $step) {
            if ($this->evaluateStepConditions($step, $data)) {
                $all_approvers[] = array(
                    'step_id' => $step['step_id'],
                    'step_name' => $step['step_name'],
                    'approver_type' => $step['approver_type'],
                    'approver_id' => $step['approver_id'],
                    'approver_name' => $step['approver_name'],
                    'is_required' => $step['is_required'],
                    'timeout_hours' => $step['timeout_hours'],
                    'sort_order' => $step['sort_order']
                );
            }
        }

        // Remove duplicates and sort
        $approval_flow = $this->deduplicateApprovers($all_approvers);

        return $approval_flow;
    }

    private function getAmountBasedApprovers($amount, $department_id = 0, $category_id = 0) {
        $sql = "SELECT pat.*,
            CASE
                WHEN pat.approver_type = 'user' THEN CONCAT(u.firstname, ' ', u.lastname)
                WHEN pat.approver_type = 'group' THEN ug.name
                ELSE 'Unknown'
            END as approver_name
            FROM " . DB_PREFIX . "purchase_approval_threshold pat
            LEFT JOIN " . DB_PREFIX . "user u ON (pat.approver_type = 'user' AND pat.approver_id = u.user_id)
            LEFT JOIN " . DB_PREFIX . "user_group ug ON (pat.approver_type = 'group' AND pat.approver_id = ug.user_group_id)
            WHERE pat.status = '1'
            AND pat.amount <= '" . (float)$amount . "'";

        if ($department_id > 0) {
            $sql .= " AND (pat.department_id = '0' OR pat.department_id = '" . (int)$department_id . "')";
        }

        if ($category_id > 0) {
            $sql .= " AND (pat.category_id = '0' OR pat.category_id = '" . (int)$category_id . "')";
        }

        $sql .= " ORDER BY pat.amount DESC, pat.sort_order ASC";

        $query = $this->db->query($sql);

        return $query->rows;
    }

    private function getDepartmentBasedApprovers($department_id, $amount) {
        if ($department_id == 0) {
            return array();
        }

        $query = $this->db->query("SELECT padr.*,
            CASE
                WHEN padr.approver_type = 'user' THEN CONCAT(u.firstname, ' ', u.lastname)
                WHEN padr.approver_type = 'group' THEN ug.name
                ELSE 'Unknown'
            END as approver_name
            FROM " . DB_PREFIX . "purchase_approval_department_rule padr
            LEFT JOIN " . DB_PREFIX . "user u ON (padr.approver_type = 'user' AND padr.approver_id = u.user_id)
            LEFT JOIN " . DB_PREFIX . "user_group ug ON (padr.approver_type = 'group' AND padr.approver_id = ug.user_group_id)
            WHERE padr.status = '1'
            AND padr.department_id = '" . (int)$department_id . "'
            AND (padr.min_amount = '0' OR padr.min_amount <= '" . (float)$amount . "')
            AND (padr.max_amount = '0' OR padr.max_amount >= '" . (float)$amount . "')
            ORDER BY padr.sort_order ASC");

        return $query->rows;
    }

    private function getCategoryBasedApprovers($category_id, $amount) {
        if ($category_id == 0) {
            return array();
        }

        $query = $this->db->query("SELECT pacr.*,
            CASE
                WHEN pacr.approver_type = 'user' THEN CONCAT(u.firstname, ' ', u.lastname)
                WHEN pacr.approver_type = 'group' THEN ug.name
                ELSE 'Unknown'
            END as approver_name
            FROM " . DB_PREFIX . "purchase_approval_category_rule pacr
            LEFT JOIN " . DB_PREFIX . "user u ON (pacr.approver_type = 'user' AND pacr.approver_id = u.user_id)
            LEFT JOIN " . DB_PREFIX . "user_group ug ON (pacr.approver_type = 'group' AND pacr.approver_id = ug.user_group_id)
            WHERE pacr.status = '1'
            AND pacr.category_id = '" . (int)$category_id . "'
            AND (pacr.min_amount = '0' OR pacr.min_amount <= '" . (float)$amount . "')
            AND (pacr.max_amount = '0' OR pacr.max_amount >= '" . (float)$amount . "')
            ORDER BY pacr.sort_order ASC");

        return $query->rows;
    }

    private function evaluateStepConditions($step, $data) {
        if (empty($step['conditions'])) {
            return true;
        }

        $conditions = json_decode($step['conditions'], true);

        if (!is_array($conditions)) {
            return true;
        }

        foreach ($conditions as $condition) {
            $field = $condition['field'] ?? '';
            $operator = $condition['operator'] ?? '=';
            $value = $condition['value'] ?? '';

            if (!$this->evaluateCondition($data, $field, $operator, $value)) {
                return false;
            }
        }

        return true;
    }

    private function evaluateCondition($data, $field, $operator, $value) {
        $data_value = $data[$field] ?? null;

        switch ($operator) {
            case '=':
                return $data_value == $value;
            case '!=':
                return $data_value != $value;
            case '>':
                return (float)$data_value > (float)$value;
            case '>=':
                return (float)$data_value >= (float)$value;
            case '<':
                return (float)$data_value < (float)$value;
            case '<=':
                return (float)$data_value <= (float)$value;
            case 'in':
                $values = explode(',', $value);
                return in_array($data_value, $values);
            case 'not_in':
                $values = explode(',', $value);
                return !in_array($data_value, $values);
            default:
                return true;
        }
    }

    private function deduplicateApprovers($approvers) {
        $unique_approvers = array();
        $seen = array();

        foreach ($approvers as $approver) {
            $key = $approver['approver_type'] . '_' . $approver['approver_id'];

            if (!isset($seen[$key])) {
                $unique_approvers[] = $approver;
                $seen[$key] = true;
            }
        }

        // Sort by sort_order
        usort($unique_approvers, function($a, $b) {
            return ($a['sort_order'] ?? 0) - ($b['sort_order'] ?? 0);
        });

        return $unique_approvers;
    }

    public function getAllSettings() {
        $settings = $this->getSettings();
        $settings['amount_thresholds'] = $this->getAmountThresholds();
        $settings['department_rules'] = $this->getDepartmentRules();
        $settings['category_rules'] = $this->getCategoryRules();
        $settings['workflow_steps'] = $this->getWorkflowSteps();

        return $settings;
    }

    public function importSettings($data) {
        // Start transaction
        $this->db->query("START TRANSACTION");

        try {
            // Import basic settings
            if (isset($data['settings'])) {
                $this->editSettings($data['settings']);
            }

            // Import amount thresholds
            if (isset($data['amount_thresholds'])) {
                $this->saveAmountThresholds($data['amount_thresholds']);
            }

            // Import department rules
            if (isset($data['department_rules'])) {
                $this->saveDepartmentRules($data['department_rules']);
            }

            // Import category rules
            if (isset($data['category_rules'])) {
                $this->saveCategoryRules($data['category_rules']);
            }

            // Import workflow steps
            if (isset($data['workflow_steps'])) {
                $this->saveWorkflow(array('workflow_steps' => $data['workflow_steps']));
            }

            // Commit transaction
            $this->db->query("COMMIT");

            return true;
        } catch (Exception $e) {
            // Rollback transaction
            $this->db->query("ROLLBACK");
            throw $e;
        }
    }

    public function getApprovalStatistics() {
        $stats = array();

        // Total approval rules
        $query = $this->db->query("SELECT COUNT(*) as total FROM " . DB_PREFIX . "purchase_approval_threshold WHERE status = '1'");
        $stats['total_thresholds'] = $query->row['total'];

        $query = $this->db->query("SELECT COUNT(*) as total FROM " . DB_PREFIX . "purchase_approval_department_rule WHERE status = '1'");
        $stats['total_department_rules'] = $query->row['total'];

        $query = $this->db->query("SELECT COUNT(*) as total FROM " . DB_PREFIX . "purchase_approval_category_rule WHERE status = '1'");
        $stats['total_category_rules'] = $query->row['total'];

        $query = $this->db->query("SELECT COUNT(*) as total FROM " . DB_PREFIX . "purchase_approval_workflow_step WHERE status = '1'");
        $stats['total_workflow_steps'] = $query->row['total'];

        return $stats;
    }

    public function validateApprovalFlow($purchase_order_data) {
        $approval_flow = $this->getApprovalFlow($purchase_order_data);

        $validation = array(
            'is_valid' => true,
            'errors' => array(),
            'warnings' => array(),
            'approval_flow' => $approval_flow
        );

        // Check if approval is required
        $settings = $this->getSettings();
        if (!($settings['approval_enabled'] ?? true)) {
            $validation['warnings'][] = 'Approval system is disabled';
            return $validation;
        }

        // Check if there are any approvers
        if (empty($approval_flow)) {
            $validation['errors'][] = 'No approvers found for this purchase order';
            $validation['is_valid'] = false;
        }

        // Check for circular approval (user approving their own request)
        $user_id = $purchase_order_data['user_id'] ?? 0;
        foreach ($approval_flow as $approver) {
            if ($approver['approver_type'] == 'user' && $approver['approver_id'] == $user_id) {
                $validation['warnings'][] = 'User cannot approve their own request: ' . $approver['approver_name'];
            }
        }

        return $validation;
    }
}
