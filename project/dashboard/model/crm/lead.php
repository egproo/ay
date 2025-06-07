<?php
/**
 * AYM ERP - Advanced CRM Lead Management Model
 *
 * Professional CRM system with comprehensive lead management
 * Features:
 * - Advanced lead scoring and qualification
 * - Multi-stage sales pipeline management
 * - Automated lead nurturing workflows
 * - Communication tracking and history
 * - Task and activity management
 * - Performance analytics and reporting
 * - Integration with marketing campaigns
 * - AI-powered lead insights
 *
 * @author AYM ERP Development Team
 * @copyright 2024 AYM ERP
 * @license Commercial License
 * @version 1.0.0
 * @link https://aym-erp.com
 */

class ModelCrmLead extends Model {

    public function addLead($data) {
        $this->db->query("INSERT INTO " . DB_PREFIX . "crm_lead SET
            firstname = '" . $this->db->escape($data['firstname']) . "',
            lastname = '" . $this->db->escape($data['lastname']) . "',
            company = '" . $this->db->escape($data['company']) . "',
            email = '" . $this->db->escape($data['email']) . "',
            phone = '" . $this->db->escape($data['phone']) . "',
            mobile = '" . $this->db->escape($data['mobile'] ?? '') . "',
            website = '" . $this->db->escape($data['website'] ?? '') . "',
            address = '" . $this->db->escape($data['address'] ?? '') . "',
            city = '" . $this->db->escape($data['city'] ?? '') . "',
            country = '" . $this->db->escape($data['country'] ?? '') . "',
            source = '" . $this->db->escape($data['source']) . "',
            status = '" . $this->db->escape($data['status']) . "',
            stage_id = '" . (int)($data['stage_id'] ?? 1) . "',
            assigned_to_user_id = '" . (int)$data['assigned_to_user_id'] . "',
            lead_value = '" . (float)($data['lead_value'] ?? 0) . "',
            probability = '" . (int)($data['probability'] ?? 0) . "',
            expected_close_date = " . (isset($data['expected_close_date']) ? "'" . $this->db->escape($data['expected_close_date']) . "'" : "NULL") . ",
            notes = '" . $this->db->escape($data['notes']) . "',
            lead_score = 0,
            date_added = NOW()");

        $lead_id = $this->db->getLastId();

        // Add lead tags if provided
        if (isset($data['tags']) && is_array($data['tags'])) {
            $this->addLeadTags($lead_id, $data['tags']);
        }

        // Add custom fields if provided
        if (isset($data['custom_fields']) && is_array($data['custom_fields'])) {
            $this->addLeadCustomFields($lead_id, $data['custom_fields']);
        }

        return $lead_id;
    }

    public function editLead($lead_id, $data) {
        $this->db->query("UPDATE " . DB_PREFIX . "crm_lead SET
            firstname = '" . $this->db->escape($data['firstname']) . "',
            lastname = '" . $this->db->escape($data['lastname']) . "',
            company = '" . $this->db->escape($data['company']) . "',
            email = '" . $this->db->escape($data['email']) . "',
            phone = '" . $this->db->escape($data['phone']) . "',
            mobile = '" . $this->db->escape($data['mobile'] ?? '') . "',
            website = '" . $this->db->escape($data['website'] ?? '') . "',
            address = '" . $this->db->escape($data['address'] ?? '') . "',
            city = '" . $this->db->escape($data['city'] ?? '') . "',
            country = '" . $this->db->escape($data['country'] ?? '') . "',
            source = '" . $this->db->escape($data['source']) . "',
            status = '" . $this->db->escape($data['status']) . "',
            stage_id = '" . (int)($data['stage_id'] ?? 1) . "',
            assigned_to_user_id = '" . (int)$data['assigned_to_user_id'] . "',
            lead_value = '" . (float)($data['lead_value'] ?? 0) . "',
            probability = '" . (int)($data['probability'] ?? 0) . "',
            expected_close_date = " . (isset($data['expected_close_date']) ? "'" . $this->db->escape($data['expected_close_date']) . "'" : "NULL") . ",
            notes = '" . $this->db->escape($data['notes']) . "',
            date_modified = NOW()
            WHERE lead_id = '" . (int)$lead_id . "'");

        // Update lead tags if provided
        if (isset($data['tags']) && is_array($data['tags'])) {
            $this->deleteLeadTags($lead_id);
            $this->addLeadTags($lead_id, $data['tags']);
        }

        // Update custom fields if provided
        if (isset($data['custom_fields']) && is_array($data['custom_fields'])) {
            $this->deleteLeadCustomFields($lead_id);
            $this->addLeadCustomFields($lead_id, $data['custom_fields']);
        }
    }

    public function deleteLead($lead_id) {
        $this->db->query("DELETE FROM " . DB_PREFIX . "crm_lead WHERE lead_id = '" . (int)$lead_id . "'");
        $this->db->query("DELETE FROM " . DB_PREFIX . "crm_lead_activity WHERE lead_id = '" . (int)$lead_id . "'");
        $this->db->query("DELETE FROM " . DB_PREFIX . "crm_lead_tag WHERE lead_id = '" . (int)$lead_id . "'");
        $this->db->query("DELETE FROM " . DB_PREFIX . "crm_lead_custom_field WHERE lead_id = '" . (int)$lead_id . "'");
        $this->db->query("DELETE FROM " . DB_PREFIX . "crm_lead_communication WHERE lead_id = '" . (int)$lead_id . "'");
    }

    public function getLead($lead_id) {
        $query = $this->db->query("SELECT l.*, s.name as stage_name, s.color as stage_color,
                                          u.firstname as assigned_firstname, u.lastname as assigned_lastname
                                  FROM " . DB_PREFIX . "crm_lead l
                                  LEFT JOIN " . DB_PREFIX . "crm_pipeline_stage s ON (l.stage_id = s.stage_id)
                                  LEFT JOIN " . DB_PREFIX . "user u ON (l.assigned_to_user_id = u.user_id)
                                  WHERE l.lead_id = '" . (int)$lead_id . "'");

        if ($query->num_rows) {
            $lead = $query->row;

            // Get lead tags
            $lead['tags'] = $this->getLeadTags($lead_id);

            // Get custom fields
            $lead['custom_fields'] = $this->getLeadCustomFields($lead_id);

            // Get recent activities
            $lead['recent_activities'] = $this->getLeadActivities($lead_id, 5);

            return $lead;
        }

        return false;
    }

    public function getLeads($data = array()) {
        $sql = "SELECT l.*, s.name as stage_name, s.color as stage_color,
                       u.firstname as assigned_firstname, u.lastname as assigned_lastname
                FROM " . DB_PREFIX . "crm_lead l
                LEFT JOIN " . DB_PREFIX . "crm_pipeline_stage s ON (l.stage_id = s.stage_id)
                LEFT JOIN " . DB_PREFIX . "user u ON (l.assigned_to_user_id = u.user_id)
                WHERE 1=1";

        if (!empty($data['filter_name'])) {
            $sql .= " AND CONCAT(l.firstname, ' ', l.lastname) LIKE '%" . $this->db->escape($data['filter_name']) . "%'";
        }

        if (!empty($data['filter_company'])) {
            $sql .= " AND l.company LIKE '%" . $this->db->escape($data['filter_company']) . "%'";
        }

        if (!empty($data['filter_email'])) {
            $sql .= " AND l.email LIKE '%" . $this->db->escape($data['filter_email']) . "%'";
        }

        if (!empty($data['filter_status'])) {
            $sql .= " AND l.status = '" . $this->db->escape($data['filter_status']) . "'";
        }

        if (!empty($data['filter_stage_id'])) {
            $sql .= " AND l.stage_id = '" . (int)$data['filter_stage_id'] . "'";
        }

        if (!empty($data['filter_assigned_to'])) {
            $sql .= " AND l.assigned_to_user_id = '" . (int)$data['filter_assigned_to'] . "'";
        }

        if (!empty($data['filter_source'])) {
            $sql .= " AND l.source = '" . $this->db->escape($data['filter_source']) . "'";
        }

        if (!empty($data['filter_date_from'])) {
            $sql .= " AND DATE(l.date_added) >= '" . $this->db->escape($data['filter_date_from']) . "'";
        }

        if (!empty($data['filter_date_to'])) {
            $sql .= " AND DATE(l.date_added) <= '" . $this->db->escape($data['filter_date_to']) . "'";
        }

        $sort_data = array(
            'name',
            'company',
            'email',
            'phone',
            'status',
            'stage_name',
            'lead_score',
            'lead_value',
            'date_added'
        );

        if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
            if ($data['sort'] == 'name') {
                $sql .= " ORDER BY CONCAT(l.firstname, ' ', l.lastname)";
            } else {
                $sql .= " ORDER BY " . $data['sort'];
            }
        } else {
            $sql .= " ORDER BY l.date_added";
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

    public function getTotalLeads($data = array()) {
        $sql = "SELECT COUNT(*) AS total FROM " . DB_PREFIX . "crm_lead l WHERE 1=1";

        if (!empty($data['filter_name'])) {
            $sql .= " AND CONCAT(l.firstname, ' ', l.lastname) LIKE '%" . $this->db->escape($data['filter_name']) . "%'";
        }

        if (!empty($data['filter_company'])) {
            $sql .= " AND l.company LIKE '%" . $this->db->escape($data['filter_company']) . "%'";
        }

        if (!empty($data['filter_email'])) {
            $sql .= " AND l.email LIKE '%" . $this->db->escape($data['filter_email']) . "%'";
        }

        if (!empty($data['filter_status'])) {
            $sql .= " AND l.status = '" . $this->db->escape($data['filter_status']) . "'";
        }

        if (!empty($data['filter_stage_id'])) {
            $sql .= " AND l.stage_id = '" . (int)$data['filter_stage_id'] . "'";
        }

        if (!empty($data['filter_assigned_to'])) {
            $sql .= " AND l.assigned_to_user_id = '" . (int)$data['filter_assigned_to'] . "'";
        }

        if (!empty($data['filter_source'])) {
            $sql .= " AND l.source = '" . $this->db->escape($data['filter_source']) . "'";
        }

        if (!empty($data['filter_date_from'])) {
            $sql .= " AND DATE(l.date_added) >= '" . $this->db->escape($data['filter_date_from']) . "'";
        }

        if (!empty($data['filter_date_to'])) {
            $sql .= " AND DATE(l.date_added) <= '" . $this->db->escape($data['filter_date_to']) . "'";
        }

        $query = $this->db->query($sql);

        return $query->row['total'];
    }

    public function getCRMStatistics() {
        $sql = "SELECT
                    COUNT(*) as total_leads,
                    COUNT(CASE WHEN status = 'new' THEN 1 END) as new_leads,
                    COUNT(CASE WHEN status = 'contacted' THEN 1 END) as contacted_leads,
                    COUNT(CASE WHEN status = 'qualified' THEN 1 END) as qualified_leads,
                    COUNT(CASE WHEN status = 'converted' THEN 1 END) as converted_leads,
                    AVG(lead_score) as avg_lead_score,
                    SUM(lead_value) as total_pipeline_value,
                    AVG(lead_value) as avg_lead_value,
                    COUNT(CASE WHEN DATE(date_added) = CURDATE() THEN 1 END) as today_leads,
                    COUNT(CASE WHEN DATE(date_added) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) THEN 1 END) as week_leads,
                    COUNT(CASE WHEN DATE(date_added) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) THEN 1 END) as month_leads
                FROM " . DB_PREFIX . "crm_lead";

        $query = $this->db->query($sql);

        return $query->row;
    }

    public function getPipelineData() {
        $sql = "SELECT
                    s.stage_id,
                    s.name as stage_name,
                    s.color as stage_color,
                    s.sort_order,
                    COUNT(l.lead_id) as lead_count,
                    SUM(l.lead_value) as stage_value,
                    AVG(l.probability) as avg_probability
                FROM " . DB_PREFIX . "crm_pipeline_stage s
                LEFT JOIN " . DB_PREFIX . "crm_lead l ON (s.stage_id = l.stage_id)
                WHERE s.status = 1
                GROUP BY s.stage_id
                ORDER BY s.sort_order";

        $query = $this->db->query($sql);

        return $query->rows;
    }

    public function getRecentActivities($limit = 10) {
        $sql = "SELECT
                    a.activity_id,
                    a.type,
                    a.subject,
                    a.description,
                    a.date_added,
                    a.date_due,
                    a.status,
                    CONCAT(l.firstname, ' ', l.lastname) as lead_name,
                    l.company,
                    u.firstname as user_firstname,
                    u.lastname as user_lastname
                FROM " . DB_PREFIX . "crm_lead_activity a
                LEFT JOIN " . DB_PREFIX . "crm_lead l ON (a.lead_id = l.lead_id)
                LEFT JOIN " . DB_PREFIX . "user u ON (a.user_id = u.user_id)
                ORDER BY a.date_added DESC
                LIMIT " . (int)$limit;

        $query = $this->db->query($sql);

        return $query->rows;
    }

    public function getTopLeads($limit = 10) {
        $sql = "SELECT
                    l.lead_id,
                    CONCAT(l.firstname, ' ', l.lastname) as lead_name,
                    l.company,
                    l.email,
                    l.phone,
                    l.lead_score,
                    l.lead_value,
                    l.probability,
                    s.name as stage_name,
                    s.color as stage_color
                FROM " . DB_PREFIX . "crm_lead l
                LEFT JOIN " . DB_PREFIX . "crm_pipeline_stage s ON (l.stage_id = s.stage_id)
                WHERE l.status != 'converted'
                ORDER BY l.lead_score DESC, l.lead_value DESC
                LIMIT " . (int)$limit;

        $query = $this->db->query($sql);

        return $query->rows;
    }

    public function getConversionFunnel() {
        $sql = "SELECT
                    s.stage_id,
                    s.name as stage_name,
                    s.sort_order,
                    COUNT(l.lead_id) as lead_count,
                    SUM(l.lead_value) as stage_value
                FROM " . DB_PREFIX . "crm_pipeline_stage s
                LEFT JOIN " . DB_PREFIX . "crm_lead l ON (s.stage_id = l.stage_id)
                WHERE s.status = 1
                GROUP BY s.stage_id
                ORDER BY s.sort_order";

        $query = $this->db->query($sql);

        $funnel_data = $query->rows;

        // Calculate conversion rates
        $total_leads = 0;
        foreach ($funnel_data as $stage) {
            $total_leads += $stage['lead_count'];
        }

        foreach ($funnel_data as &$stage) {
            $stage['conversion_rate'] = $total_leads > 0 ? ($stage['lead_count'] / $total_leads) * 100 : 0;
        }

        return $funnel_data;
    }

    public function getPerformanceMetrics() {
        $sql = "SELECT
                    u.user_id,
                    CONCAT(u.firstname, ' ', u.lastname) as user_name,
                    COUNT(l.lead_id) as total_leads,
                    COUNT(CASE WHEN l.status = 'converted' THEN 1 END) as converted_leads,
                    SUM(l.lead_value) as total_value,
                    AVG(l.lead_score) as avg_score,
                    (COUNT(CASE WHEN l.status = 'converted' THEN 1 END) / COUNT(l.lead_id) * 100) as conversion_rate
                FROM " . DB_PREFIX . "user u
                LEFT JOIN " . DB_PREFIX . "crm_lead l ON (u.user_id = l.assigned_to_user_id)
                WHERE u.status = 1
                GROUP BY u.user_id
                HAVING total_leads > 0
                ORDER BY conversion_rate DESC, total_value DESC";

        $query = $this->db->query($sql);

        return $query->rows;
    }

    public function getUpcomingTasks($limit = 10) {
        $sql = "SELECT
                    a.activity_id,
                    a.type,
                    a.subject,
                    a.description,
                    a.date_due,
                    a.priority,
                    CONCAT(l.firstname, ' ', l.lastname) as lead_name,
                    l.company,
                    u.firstname as user_firstname,
                    u.lastname as user_lastname
                FROM " . DB_PREFIX . "crm_lead_activity a
                LEFT JOIN " . DB_PREFIX . "crm_lead l ON (a.lead_id = l.lead_id)
                LEFT JOIN " . DB_PREFIX . "user u ON (a.user_id = u.user_id)
                WHERE a.status = 'pending'
                AND a.date_due >= NOW()
                ORDER BY a.date_due ASC, a.priority DESC
                LIMIT " . (int)$limit;

        $query = $this->db->query($sql);

        return $query->rows;
    }

    public function getPipelineStages() {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "crm_pipeline_stage
                                  WHERE status = 1
                                  ORDER BY sort_order");

        return $query->rows;
    }

    public function getLeadsByStage($stage_id) {
        $query = $this->db->query("SELECT l.*,
                                          CONCAT(l.firstname, ' ', l.lastname) as lead_name,
                                          u.firstname as assigned_firstname,
                                          u.lastname as assigned_lastname
                                  FROM " . DB_PREFIX . "crm_lead l
                                  LEFT JOIN " . DB_PREFIX . "user u ON (l.assigned_to_user_id = u.user_id)
                                  WHERE l.stage_id = '" . (int)$stage_id . "'
                                  ORDER BY l.lead_score DESC, l.date_added DESC");

        return $query->rows;
    }

    public function getPipelineStatistics() {
        $sql = "SELECT
                    COUNT(DISTINCT l.lead_id) as total_leads,
                    SUM(l.lead_value) as total_value,
                    AVG(l.probability) as avg_probability,
                    COUNT(DISTINCT s.stage_id) as total_stages,
                    AVG(DATEDIFF(NOW(), l.date_added)) as avg_days_in_pipeline
                FROM " . DB_PREFIX . "crm_lead l
                LEFT JOIN " . DB_PREFIX . "crm_pipeline_stage s ON (l.stage_id = s.stage_id)
                WHERE l.status != 'converted'";

        $query = $this->db->query($sql);

        return $query->row;
    }

    public function moveLeadToStage($lead_id, $stage_id, $notes = '') {
        // Start transaction
        $this->db->query("START TRANSACTION");

        try {
            // Get current stage
            $current_lead = $this->getLead($lead_id);

            if (!$current_lead) {
                throw new Exception('Lead not found');
            }

            // Update lead stage
            $this->db->query("UPDATE " . DB_PREFIX . "crm_lead SET
                             stage_id = '" . (int)$stage_id . "',
                             date_modified = NOW()
                             WHERE lead_id = '" . (int)$lead_id . "'");

            // Add activity for stage change
            $this->addActivity($lead_id, array(
                'type' => 'stage_change',
                'subject' => 'Stage Changed',
                'description' => 'Lead moved from stage ' . $current_lead['stage_name'] . ' to new stage. ' . $notes,
                'user_id' => $this->user->getId(),
                'date_due' => date('Y-m-d H:i:s', strtotime('+1 day'))
            ));

            $this->db->query("COMMIT");

            return true;

        } catch (Exception $e) {
            $this->db->query("ROLLBACK");
            throw $e;
        }
    }

    public function addActivity($lead_id, $data) {
        $this->db->query("INSERT INTO " . DB_PREFIX . "crm_lead_activity SET
                         lead_id = '" . (int)$lead_id . "',
                         type = '" . $this->db->escape($data['type']) . "',
                         subject = '" . $this->db->escape($data['subject']) . "',
                         description = '" . $this->db->escape($data['description']) . "',
                         date_due = '" . $this->db->escape($data['date_due']) . "',
                         priority = '" . $this->db->escape($data['priority'] ?? 'normal') . "',
                         status = '" . $this->db->escape($data['status'] ?? 'pending') . "',
                         user_id = '" . (int)$data['user_id'] . "',
                         date_added = NOW()");

        return $this->db->getLastId();
    }

    public function getLeadActivities($lead_id, $limit = 20) {
        $query = $this->db->query("SELECT a.*,
                                          u.firstname as user_firstname,
                                          u.lastname as user_lastname
                                  FROM " . DB_PREFIX . "crm_lead_activity a
                                  LEFT JOIN " . DB_PREFIX . "user u ON (a.user_id = u.user_id)
                                  WHERE a.lead_id = '" . (int)$lead_id . "'
                                  ORDER BY a.date_added DESC
                                  LIMIT " . (int)$limit);

        return $query->rows;
    }
