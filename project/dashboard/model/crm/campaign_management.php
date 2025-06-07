<?php
/**
 * نموذج إدارة الحملات التسويقية (Campaign Management Model)
 *
 * الهدف: إدارة الحملات التسويقية وتتبع أدائها وحساب العائد على الاستثمار
 * الميزات: حملات متعددة القنوات، تتبع شامل، تحليل ROI، تكامل محاسبي
 *
 * @author ERP Team
 * @version 1.0
 * @since 2024
 */

class ModelCrmCampaignManagement extends Model {

    /**
     * الحصول على الحملات مع الفلاتر
     */
    public function getCampaigns($data = []) {
        $sql = "
            SELECT
                c.*,
                u.firstname as created_by_name,
                (SELECT COUNT(*) FROM " . DB_PREFIX . "campaign_lead cl WHERE cl.campaign_id = c.campaign_id) as leads_generated,
                (SELECT COUNT(*) FROM " . DB_PREFIX . "campaign_lead cl WHERE cl.campaign_id = c.campaign_id AND cl.status = 'converted') as conversions,
                (SELECT SUM(o.total) FROM " . DB_PREFIX . "campaign_lead cl LEFT JOIN " . DB_PREFIX . "order o ON cl.customer_id = o.customer_id WHERE cl.campaign_id = c.campaign_id AND o.order_status_id > 0) as revenue_generated,
                CASE
                    WHEN c.budget > 0 AND c.spent > 0 THEN
                        ((SELECT SUM(o.total) FROM " . DB_PREFIX . "campaign_lead cl LEFT JOIN " . DB_PREFIX . "order o ON cl.customer_id = o.customer_id WHERE cl.campaign_id = c.campaign_id AND o.order_status_id > 0) - c.spent) / c.spent * 100
                    ELSE 0
                END as roi,
                CASE
                    WHEN c.target_audience > 0 THEN
                        (c.reached_audience / c.target_audience * 100)
                    ELSE 0
                END as reach_percentage,
                CASE
                    WHEN (SELECT COUNT(*) FROM " . DB_PREFIX . "campaign_lead cl WHERE cl.campaign_id = c.campaign_id) > 0 THEN
                        ((SELECT COUNT(*) FROM " . DB_PREFIX . "campaign_lead cl WHERE cl.campaign_id = c.campaign_id AND cl.status = 'converted') / (SELECT COUNT(*) FROM " . DB_PREFIX . "campaign_lead cl WHERE cl.campaign_id = c.campaign_id) * 100)
                    ELSE 0
                END as conversion_rate,
                (
                    (CASE WHEN c.target_audience > 0 THEN (c.reached_audience / c.target_audience * 100) ELSE 0 END * 0.3) +
                    (CASE WHEN (SELECT COUNT(*) FROM " . DB_PREFIX . "campaign_lead cl WHERE cl.campaign_id = c.campaign_id) > 0 THEN ((SELECT COUNT(*) FROM " . DB_PREFIX . "campaign_lead cl WHERE cl.campaign_id = c.campaign_id AND cl.status = 'converted') / (SELECT COUNT(*) FROM " . DB_PREFIX . "campaign_lead cl WHERE cl.campaign_id = c.campaign_id) * 100) ELSE 0 END * 0.4) +
                    (CASE WHEN c.budget > 0 THEN (100 - (c.spent / c.budget * 100)) ELSE 0 END * 0.3)
                ) as performance_score
            FROM " . DB_PREFIX . "campaign c
            LEFT JOIN " . DB_PREFIX . "user u ON (c.created_by = u.user_id)
            WHERE 1=1
        ";

        // تطبيق الفلاتر
        if (!empty($data['filter_name'])) {
            $sql .= " AND c.name LIKE '%" . $this->db->escape($data['filter_name']) . "%'";
        }

        if (!empty($data['filter_type'])) {
            $sql .= " AND c.type = '" . $this->db->escape($data['filter_type']) . "'";
        }

        if (!empty($data['filter_status'])) {
            $sql .= " AND c.status = '" . $this->db->escape($data['filter_status']) . "'";
        }

        if (!empty($data['filter_performance'])) {
            $range = explode('-', $data['filter_performance']);
            if (count($range) == 2) {
                $sql .= " HAVING performance_score BETWEEN '" . (int)$range[0] . "' AND '" . (int)$range[1] . "'";
            }
        }

        if (!empty($data['filter_date_from'])) {
            $sql .= " AND DATE(c.start_date) >= '" . $this->db->escape($data['filter_date_from']) . "'";
        }

        if (!empty($data['filter_date_to'])) {
            $sql .= " AND DATE(c.end_date) <= '" . $this->db->escape($data['filter_date_to']) . "'";
        }

        // ترتيب النتائج
        $sort_data = [
            'name',
            'type',
            'status',
            'budget',
            'spent',
            'leads_generated',
            'conversions',
            'revenue_generated',
            'roi',
            'performance_score',
            'start_date',
            'end_date',
            'date_created'
        ];

        if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
            $sql .= " ORDER BY " . $data['sort'];
        } else {
            $sql .= " ORDER BY c.date_created";
        }

        if (isset($data['order']) && ($data['order'] == 'DESC')) {
            $sql .= " DESC";
        } else {
            $sql .= " ASC";
        }

        // تحديد عدد النتائج
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
     * الحصول على إجمالي عدد الحملات
     */
    public function getTotalCampaigns($data = []) {
        $sql = "
            SELECT COUNT(DISTINCT c.campaign_id) AS total
            FROM " . DB_PREFIX . "campaign c
            WHERE 1=1
        ";

        // تطبيق نفس الفلاتر
        if (!empty($data['filter_name'])) {
            $sql .= " AND c.name LIKE '%" . $this->db->escape($data['filter_name']) . "%'";
        }

        if (!empty($data['filter_type'])) {
            $sql .= " AND c.type = '" . $this->db->escape($data['filter_type']) . "'";
        }

        if (!empty($data['filter_status'])) {
            $sql .= " AND c.status = '" . $this->db->escape($data['filter_status']) . "'";
        }

        $query = $this->db->query($sql);

        return $query->row['total'];
    }

    /**
     * الحصول على حملة محددة
     */
    public function getCampaign($campaign_id) {
        $query = $this->db->query("
            SELECT
                c.*,
                u.firstname as created_by_name,
                (SELECT COUNT(*) FROM " . DB_PREFIX . "campaign_lead cl WHERE cl.campaign_id = c.campaign_id) as leads_generated,
                (SELECT COUNT(*) FROM " . DB_PREFIX . "campaign_lead cl WHERE cl.campaign_id = c.campaign_id AND cl.status = 'converted') as conversions,
                (SELECT SUM(o.total) FROM " . DB_PREFIX . "campaign_lead cl LEFT JOIN " . DB_PREFIX . "order o ON cl.customer_id = o.customer_id WHERE cl.campaign_id = c.campaign_id AND o.order_status_id > 0) as revenue_generated
            FROM " . DB_PREFIX . "campaign c
            LEFT JOIN " . DB_PREFIX . "user u ON (c.created_by = u.user_id)
            WHERE c.campaign_id = '" . (int)$campaign_id . "'
        ");

        return $query->num_rows ? $query->row : false;
    }

    /**
     * إضافة حملة جديدة
     */
    public function addCampaign($data) {
        $this->db->query("
            INSERT INTO " . DB_PREFIX . "campaign SET
                name = '" . $this->db->escape($data['name']) . "',
                description = '" . $this->db->escape($data['description']) . "',
                type = '" . $this->db->escape($data['type']) . "',
                status = '" . $this->db->escape($data['status']) . "',
                budget = '" . (float)$data['budget'] . "',
                spent = 0,
                target_audience = '" . (int)($data['target_audience'] ?? 0) . "',
                reached_audience = 0,
                start_date = '" . $this->db->escape($data['start_date']) . "',
                end_date = '" . $this->db->escape($data['end_date']) . "',
                objectives = '" . $this->db->escape($data['objectives'] ?? '') . "',
                target_demographics = '" . $this->db->escape(json_encode($data['target_demographics'] ?? [])) . "',
                channels = '" . $this->db->escape(json_encode($data['channels'] ?? [])) . "',
                created_by = '" . (int)$this->user->getId() . "',
                date_created = NOW()
        ");

        $campaign_id = $this->db->getLastId();

        // إنشاء أهداف الحملة
        if (!empty($data['goals'])) {
            $this->addCampaignGoals($campaign_id, $data['goals']);
        }

        return $campaign_id;
    }

    /**
     * تحديث حملة
     */
    public function editCampaign($campaign_id, $data) {
        $this->db->query("
            UPDATE " . DB_PREFIX . "campaign SET
                name = '" . $this->db->escape($data['name']) . "',
                description = '" . $this->db->escape($data['description']) . "',
                type = '" . $this->db->escape($data['type']) . "',
                status = '" . $this->db->escape($data['status']) . "',
                budget = '" . (float)$data['budget'] . "',
                target_audience = '" . (int)($data['target_audience'] ?? 0) . "',
                start_date = '" . $this->db->escape($data['start_date']) . "',
                end_date = '" . $this->db->escape($data['end_date']) . "',
                objectives = '" . $this->db->escape($data['objectives'] ?? '') . "',
                target_demographics = '" . $this->db->escape(json_encode($data['target_demographics'] ?? [])) . "',
                channels = '" . $this->db->escape(json_encode($data['channels'] ?? [])) . "',
                date_modified = NOW()
            WHERE campaign_id = '" . (int)$campaign_id . "'
        ");
    }

    /**
     * حذف حملة
     */
    public function deleteCampaign($campaign_id) {
        $this->db->query("DELETE FROM " . DB_PREFIX . "campaign WHERE campaign_id = '" . (int)$campaign_id . "'");
        $this->db->query("DELETE FROM " . DB_PREFIX . "campaign_lead WHERE campaign_id = '" . (int)$campaign_id . "'");
        $this->db->query("DELETE FROM " . DB_PREFIX . "campaign_goal WHERE campaign_id = '" . (int)$campaign_id . "'");
        $this->db->query("DELETE FROM " . DB_PREFIX . "campaign_activity WHERE campaign_id = '" . (int)$campaign_id . "'");
    }

    /**
     * نسخ حملة
     */
    public function duplicateCampaign($campaign_id) {
        $campaign = $this->getCampaign($campaign_id);

        if (!$campaign) {
            return false;
        }

        // إنشاء نسخة جديدة
        $new_data = [
            'name' => $campaign['name'] . ' - نسخة',
            'description' => $campaign['description'],
            'type' => $campaign['type'],
            'status' => 'draft',
            'budget' => $campaign['budget'],
            'target_audience' => $campaign['target_audience'],
            'start_date' => date('Y-m-d'),
            'end_date' => date('Y-m-d', strtotime('+1 month')),
            'objectives' => $campaign['objectives'],
            'target_demographics' => json_decode($campaign['target_demographics'], true),
            'channels' => json_decode($campaign['channels'], true)
        ];

        return $this->addCampaign($new_data);
    }

    /**
     * إضافة عميل محتمل للحملة
     */
    public function addCampaignLead($campaign_id, $customer_id, $source = '', $cost = 0) {
        $this->db->query("
            INSERT INTO " . DB_PREFIX . "campaign_lead SET
                campaign_id = '" . (int)$campaign_id . "',
                customer_id = '" . (int)$customer_id . "',
                source = '" . $this->db->escape($source) . "',
                cost = '" . (float)$cost . "',
                status = 'new',
                date_created = NOW()
        ");

        $lead_id = $this->db->getLastId();

        // تحديث إحصائيات الحملة
        $this->updateCampaignStats($campaign_id);

        return $lead_id;
    }

    /**
     * تحديث حالة العميل المحتمل
     */
    public function updateLeadStatus($lead_id, $status) {
        $this->db->query("
            UPDATE " . DB_PREFIX . "campaign_lead
            SET status = '" . $this->db->escape($status) . "',
                date_modified = NOW()
            WHERE lead_id = '" . (int)$lead_id . "'
        ");

        // الحصول على معرف الحملة لتحديث الإحصائيات
        $query = $this->db->query("
            SELECT campaign_id
            FROM " . DB_PREFIX . "campaign_lead
            WHERE lead_id = '" . (int)$lead_id . "'
        ");

        if ($query->num_rows) {
            $this->updateCampaignStats($query->row['campaign_id']);
        }
    }

    /**
     * تسجيل نشاط للحملة
     */
    public function addCampaignActivity($campaign_id, $activity_type, $description, $cost = 0, $reach = 0) {
        $this->db->query("
            INSERT INTO " . DB_PREFIX . "campaign_activity SET
                campaign_id = '" . (int)$campaign_id . "',
                activity_type = '" . $this->db->escape($activity_type) . "',
                description = '" . $this->db->escape($description) . "',
                cost = '" . (float)$cost . "',
                reach = '" . (int)$reach . "',
                created_by = '" . (int)$this->user->getId() . "',
                date_created = NOW()
        ");

        // تحديث المصروفات والوصول
        $this->db->query("
            UPDATE " . DB_PREFIX . "campaign
            SET spent = spent + '" . (float)$cost . "',
                reached_audience = reached_audience + '" . (int)$reach . "'
            WHERE campaign_id = '" . (int)$campaign_id . "'
        ");

        return $this->db->getLastId();
    }

    /**
     * تحديث إحصائيات الحملة
     */
    private function updateCampaignStats($campaign_id) {
        // حساب عدد العملاء المحتملين
        $leads_query = $this->db->query("
            SELECT COUNT(*) as total
            FROM " . DB_PREFIX . "campaign_lead
            WHERE campaign_id = '" . (int)$campaign_id . "'
        ");

        // حساب عدد التحويلات
        $conversions_query = $this->db->query("
            SELECT COUNT(*) as total
            FROM " . DB_PREFIX . "campaign_lead
            WHERE campaign_id = '" . (int)$campaign_id . "' AND status = 'converted'
        ");

        // حساب الإيرادات
        $revenue_query = $this->db->query("
            SELECT SUM(o.total) as total
            FROM " . DB_PREFIX . "campaign_lead cl
            LEFT JOIN " . DB_PREFIX . "order o ON cl.customer_id = o.customer_id
            WHERE cl.campaign_id = '" . (int)$campaign_id . "' AND o.order_status_id > 0
        ");

        $leads = $leads_query->row['total'];
        $conversions = $conversions_query->row['total'];
        $revenue = $revenue_query->row['total'] ?? 0;

        // تحديث الحملة
        $this->db->query("
            UPDATE " . DB_PREFIX . "campaign
            SET leads_generated = '" . (int)$leads . "',
                conversions = '" . (int)$conversions . "',
                revenue_generated = '" . (float)$revenue . "',
                date_modified = NOW()
            WHERE campaign_id = '" . (int)$campaign_id . "'
        ");
    }

    /**
     * إضافة أهداف الحملة
     */
    private function addCampaignGoals($campaign_id, $goals) {
        foreach ($goals as $goal) {
            $this->db->query("
                INSERT INTO " . DB_PREFIX . "campaign_goal SET
                    campaign_id = '" . (int)$campaign_id . "',
                    goal_type = '" . $this->db->escape($goal['type']) . "',
                    goal_name = '" . $this->db->escape($goal['name']) . "',
                    target_value = '" . (float)$goal['target'] . "',
                    current_value = 0,
                    date_created = NOW()
            ");
        }
    }

    /**
     * الحصول على عملاء الحملة المحتملين
     */
    public function getCampaignLeads($campaign_id) {
        $query = $this->db->query("
            SELECT
                cl.*,
                CONCAT(c.firstname, ' ', c.lastname) as customer_name,
                c.email,
                c.telephone as phone
            FROM " . DB_PREFIX . "campaign_lead cl
            LEFT JOIN " . DB_PREFIX . "customer c ON cl.customer_id = c.customer_id
            WHERE cl.campaign_id = '" . (int)$campaign_id . "'
            ORDER BY cl.date_created DESC
        ");

        return $query->rows;
    }

    /**
     * الحصول على أنشطة الحملة
     */
    public function getCampaignActivities($campaign_id) {
        $query = $this->db->query("
            SELECT
                ca.*,
                u.firstname as created_by_name
            FROM " . DB_PREFIX . "campaign_activity ca
            LEFT JOIN " . DB_PREFIX . "user u ON ca.created_by = u.user_id
            WHERE ca.campaign_id = '" . (int)$campaign_id . "'
            ORDER BY ca.date_created DESC
        ");

        return $query->rows;
    }

    /**
     * إحصائيات سريعة
     */
    public function getActiveCampaigns() {
        $query = $this->db->query("
            SELECT COUNT(*) as total
            FROM " . DB_PREFIX . "campaign
            WHERE status = 'active'
        ");
        return $query->row['total'];
    }

    public function getTotalBudget() {
        $query = $this->db->query("
            SELECT SUM(budget) as total
            FROM " . DB_PREFIX . "campaign
            WHERE status IN ('active', 'completed')
        ");
        return $query->row['total'] ?? 0;
    }

    public function getTotalSpent() {
        $query = $this->db->query("
            SELECT SUM(spent) as total
            FROM " . DB_PREFIX . "campaign
            WHERE status IN ('active', 'completed')
        ");
        return $query->row['total'] ?? 0;
    }

    public function getTotalLeadsGenerated() {
        $query = $this->db->query("
            SELECT COUNT(*) as total
            FROM " . DB_PREFIX . "campaign_lead
        ");
        return $query->row['total'];
    }

    public function getAverageROI() {
        $query = $this->db->query("
            SELECT
                AVG(
                    CASE
                        WHEN c.spent > 0 THEN
                            ((SELECT SUM(o.total) FROM " . DB_PREFIX . "campaign_lead cl LEFT JOIN " . DB_PREFIX . "order o ON cl.customer_id = o.customer_id WHERE cl.campaign_id = c.campaign_id AND o.order_status_id > 0) - c.spent) / c.spent * 100
                        ELSE 0
                    END
                ) as avg_roi
            FROM " . DB_PREFIX . "campaign c
            WHERE c.spent > 0
        ");
        return round($query->row['avg_roi'], 1);
    }

    public function getBestPerformingCampaign() {
        $query = $this->db->query("
            SELECT
                name,
                CASE
                    WHEN spent > 0 THEN
                        ((SELECT SUM(o.total) FROM " . DB_PREFIX . "campaign_lead cl LEFT JOIN " . DB_PREFIX . "order o ON cl.customer_id = o.customer_id WHERE cl.campaign_id = c.campaign_id AND o.order_status_id > 0) - spent) / spent * 100
                    ELSE 0
                END as roi
            FROM " . DB_PREFIX . "campaign c
            WHERE spent > 0
            ORDER BY roi DESC
            LIMIT 1
        ");
        return $query->num_rows ? $query->row['name'] : 'لا يوجد';
    }

    public function getOverallConversionRate() {
        $total_leads_query = $this->db->query("SELECT COUNT(*) as total FROM " . DB_PREFIX . "campaign_lead");
        $converted_leads_query = $this->db->query("
            SELECT COUNT(*) as total
            FROM " . DB_PREFIX . "campaign_lead
            WHERE status = 'converted'
        ");

        $total = $total_leads_query->row['total'];
        $converted = $converted_leads_query->row['total'];

        return $total > 0 ? round(($converted / $total) * 100, 1) : 0;
    }

    /**
     * الحصول على إحصائيات الحملات
     */
    public function getCampaignStatistics() {
        $query = $this->db->query("
            SELECT
                COUNT(*) as total_campaigns,
                COUNT(CASE WHEN status = 'active' THEN 1 END) as active_campaigns,
                COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_campaigns,
                COUNT(CASE WHEN status = 'paused' THEN 1 END) as paused_campaigns,
                SUM(budget) as total_budget,
                SUM(spent_amount) as total_spent,
                AVG(budget) as avg_budget,
                AVG(spent_amount) as avg_spent
            FROM " . DB_PREFIX . "crm_campaign
        ");

        return $query->row;
    }

    /**
     * الحصول على عدد الحملات النشطة
     */
    public function getActiveCampaignsCount() {
        $query = $this->db->query("
            SELECT COUNT(*) as total
            FROM " . DB_PREFIX . "crm_campaign
            WHERE status = 'active'
        ");

        return $query->row['total'];
    }

    /**
     * الحصول على إجمالي الميزانية
     */
    public function getTotalBudget() {
        $query = $this->db->query("
            SELECT SUM(budget) as total
            FROM " . DB_PREFIX . "crm_campaign
            WHERE status IN ('active', 'paused')
        ");

        return $query->row['total'] ?: 0;
    }

    /**
     * الحصول على إجمالي المبلغ المنفق
     */
    public function getTotalSpent() {
        $query = $this->db->query("
            SELECT SUM(spent_amount) as total
            FROM " . DB_PREFIX . "crm_campaign
        ");

        return $query->row['total'] ?: 0;
    }

    /**
     * الحصول على متوسط ROI
     */
    public function getAverageROI() {
        $query = $this->db->query("
            SELECT
                AVG(CASE
                    WHEN c.spent_amount > 0 THEN
                        ((SELECT SUM(l.estimated_value) FROM " . DB_PREFIX . "crm_lead l
                          WHERE l.campaign_id = c.campaign_id AND l.status = 'converted') - c.spent_amount) / c.spent_amount * 100
                    ELSE 0
                END) as avg_roi
            FROM " . DB_PREFIX . "crm_campaign c
            WHERE c.spent_amount > 0
        ");

        return round($query->row['avg_roi'], 2);
    }

    /**
     * الحصول على أفضل الحملات أداءً
     */
    public function getTopPerformingCampaigns($limit = 5) {
        $query = $this->db->query("
            SELECT c.*,
                   (SELECT COUNT(*) FROM " . DB_PREFIX . "crm_lead l
                    WHERE l.campaign_id = c.campaign_id) as lead_count,
                   (SELECT COUNT(*) FROM " . DB_PREFIX . "crm_lead l
                    WHERE l.campaign_id = c.campaign_id AND l.status = 'converted') as converted_count,
                   CASE
                       WHEN (SELECT COUNT(*) FROM " . DB_PREFIX . "crm_lead l WHERE l.campaign_id = c.campaign_id) > 0
                       THEN (SELECT COUNT(*) FROM " . DB_PREFIX . "crm_lead l WHERE l.campaign_id = c.campaign_id AND l.status = 'converted') /
                            (SELECT COUNT(*) FROM " . DB_PREFIX . "crm_lead l WHERE l.campaign_id = c.campaign_id) * 100
                       ELSE 0
                   END as conversion_rate,
                   CASE
                       WHEN c.spent_amount > 0 THEN
                           ((SELECT SUM(l.estimated_value) FROM " . DB_PREFIX . "crm_lead l
                             WHERE l.campaign_id = c.campaign_id AND l.status = 'converted') - c.spent_amount) / c.spent_amount * 100
                       ELSE 0
                   END as roi_percentage
            FROM " . DB_PREFIX . "crm_campaign c
            WHERE c.status IN ('active', 'completed')
            ORDER BY roi_percentage DESC, conversion_rate DESC
            LIMIT " . (int)$limit
        );

        return $query->rows;
    }

    /**
     * الحصول على استخدام الميزانية
     */
    public function getBudgetUtilization() {
        $query = $this->db->query("
            SELECT
                campaign_type,
                SUM(budget) as total_budget,
                SUM(spent_amount) as total_spent,
                (SUM(spent_amount) / SUM(budget) * 100) as utilization_rate
            FROM " . DB_PREFIX . "crm_campaign
            WHERE budget > 0
            GROUP BY campaign_type
            ORDER BY utilization_rate DESC
        ");

        return $query->rows;
    }

    /**
     * الحصول على معدلات التحويل
     */
    public function getConversionRates() {
        $query = $this->db->query("
            SELECT
                c.campaign_type,
                COUNT(DISTINCT c.campaign_id) as campaign_count,
                COUNT(l.lead_id) as total_leads,
                COUNT(CASE WHEN l.status = 'converted' THEN 1 END) as converted_leads,
                (COUNT(CASE WHEN l.status = 'converted' THEN 1 END) / COUNT(l.lead_id) * 100) as conversion_rate
            FROM " . DB_PREFIX . "crm_campaign c
            LEFT JOIN " . DB_PREFIX . "crm_lead l ON (c.campaign_id = l.campaign_id)
            GROUP BY c.campaign_type
            ORDER BY conversion_rate DESC
        ");

        return $query->rows;
    }

    /**
     * الحصول على أداء القنوات
     */
    public function getChannelPerformance() {
        $query = $this->db->query("
            SELECT
                c.campaign_type as channel,
                COUNT(*) as campaign_count,
                SUM(c.budget) as total_budget,
                SUM(c.spent_amount) as total_spent,
                COUNT(l.lead_id) as total_leads,
                COUNT(CASE WHEN l.status = 'converted' THEN 1 END) as converted_leads,
                (COUNT(CASE WHEN l.status = 'converted' THEN 1 END) / COUNT(l.lead_id) * 100) as conversion_rate,
                (SUM(c.spent_amount) / COUNT(l.lead_id)) as cost_per_lead,
                (SUM(c.spent_amount) / COUNT(CASE WHEN l.status = 'converted' THEN 1 END)) as cost_per_conversion
            FROM " . DB_PREFIX . "crm_campaign c
            LEFT JOIN " . DB_PREFIX . "crm_lead l ON (c.campaign_id = l.campaign_id)
            GROUP BY c.campaign_type
            ORDER BY conversion_rate DESC
        ");

        return $query->rows;
    }

    /**
     * الحصول على اتجاه الأداء
     */
    public function getPerformanceTrend($days = 30) {
        $query = $this->db->query("
            SELECT
                DATE(l.date_created) as trend_date,
                COUNT(l.lead_id) as daily_leads,
                COUNT(CASE WHEN l.status = 'converted' THEN 1 END) as daily_conversions,
                (COUNT(CASE WHEN l.status = 'converted' THEN 1 END) / COUNT(l.lead_id) * 100) as daily_conversion_rate,
                SUM(CASE WHEN l.status = 'converted' THEN l.estimated_value ELSE 0 END) as daily_revenue
            FROM " . DB_PREFIX . "crm_lead l
            JOIN " . DB_PREFIX . "crm_campaign c ON (l.campaign_id = c.campaign_id)
            WHERE l.date_created >= DATE_SUB(NOW(), INTERVAL " . (int)$days . " DAY)
            GROUP BY DATE(l.date_created)
            ORDER BY trend_date ASC
        ");

        return $query->rows;
    }

    /**
     * الحصول على الحملات منتهية الصلاحية
     */
    public function getExpiredCampaigns() {
        $query = $this->db->query("
            SELECT c.*, u.firstname, u.lastname,
                   DATEDIFF(NOW(), c.end_date) as days_expired
            FROM " . DB_PREFIX . "crm_campaign c
            LEFT JOIN " . DB_PREFIX . "user u ON (c.created_by = u.user_id)
            WHERE c.status = 'active'
            AND c.end_date < NOW()
            ORDER BY c.end_date DESC
        ");

        return $query->rows;
    }

    /**
     * الحصول على الحملات التي تجاوزت الميزانية
     */
    public function getOverBudgetCampaigns() {
        $query = $this->db->query("
            SELECT c.*, u.firstname, u.lastname,
                   (c.spent_amount - c.budget) as over_budget_amount,
                   ((c.spent_amount - c.budget) / c.budget * 100) as over_budget_percentage
            FROM " . DB_PREFIX . "crm_campaign c
            LEFT JOIN " . DB_PREFIX . "user u ON (c.created_by = u.user_id)
            WHERE c.spent_amount > c.budget
            ORDER BY over_budget_percentage DESC
        ");

        return $query->rows;
    }

    /**
     * الحصول على الحملات ضعيفة الأداء
     */
    public function getPoorPerformingCampaigns($min_leads = 10) {
        $query = $this->db->query("
            SELECT c.*, u.firstname, u.lastname,
                   (SELECT COUNT(*) FROM " . DB_PREFIX . "crm_lead l
                    WHERE l.campaign_id = c.campaign_id) as lead_count,
                   (SELECT COUNT(*) FROM " . DB_PREFIX . "crm_lead l
                    WHERE l.campaign_id = c.campaign_id AND l.status = 'converted') as converted_count,
                   CASE
                       WHEN (SELECT COUNT(*) FROM " . DB_PREFIX . "crm_lead l WHERE l.campaign_id = c.campaign_id) > 0
                       THEN (SELECT COUNT(*) FROM " . DB_PREFIX . "crm_lead l WHERE l.campaign_id = c.campaign_id AND l.status = 'converted') /
                            (SELECT COUNT(*) FROM " . DB_PREFIX . "crm_lead l WHERE l.campaign_id = c.campaign_id) * 100
                       ELSE 0
                   END as conversion_rate
            FROM " . DB_PREFIX . "crm_campaign c
            LEFT JOIN " . DB_PREFIX . "user u ON (c.created_by = u.user_id)
            WHERE c.status = 'active'
            AND (SELECT COUNT(*) FROM " . DB_PREFIX . "crm_lead l WHERE l.campaign_id = c.campaign_id) >= " . (int)$min_leads . "
            HAVING conversion_rate < 5
            ORDER BY conversion_rate ASC
        ");

        return $query->rows;
    }

    /**
     * الحصول على ملخص الأداء الشهري
     */
    public function getMonthlyPerformanceSummary($months = 12) {
        $query = $this->db->query("
            SELECT
                DATE_FORMAT(l.date_created, '%Y-%m') as month,
                COUNT(l.lead_id) as total_leads,
                COUNT(CASE WHEN l.status = 'converted' THEN 1 END) as converted_leads,
                (COUNT(CASE WHEN l.status = 'converted' THEN 1 END) / COUNT(l.lead_id) * 100) as conversion_rate,
                SUM(CASE WHEN l.status = 'converted' THEN l.estimated_value ELSE 0 END) as revenue,
                COUNT(DISTINCT l.campaign_id) as active_campaigns
            FROM " . DB_PREFIX . "crm_lead l
            JOIN " . DB_PREFIX . "crm_campaign c ON (l.campaign_id = c.campaign_id)
            WHERE l.date_created >= DATE_SUB(NOW(), INTERVAL " . (int)$months . " MONTH)
            GROUP BY DATE_FORMAT(l.date_created, '%Y-%m')
            ORDER BY month DESC
        ");

        return $query->rows;
    }

    /**
     * البحث في الحملات
     */
    public function searchCampaigns($search_term, $limit = 20) {
        $query = $this->db->query("
            SELECT c.*, u.firstname, u.lastname
            FROM " . DB_PREFIX . "crm_campaign c
            LEFT JOIN " . DB_PREFIX . "user u ON (c.created_by = u.user_id)
            WHERE c.name LIKE '%" . $this->db->escape($search_term) . "%'
            OR c.description LIKE '%" . $this->db->escape($search_term) . "%'
            OR c.campaign_type LIKE '%" . $this->db->escape($search_term) . "%'
            OR CONCAT(u.firstname, ' ', u.lastname) LIKE '%" . $this->db->escape($search_term) . "%'
            ORDER BY c.date_created DESC
            LIMIT " . (int)$limit
        );

        return $query->rows;
    }
}
