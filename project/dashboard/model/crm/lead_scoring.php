<?php
/**
 * نموذج تقييم العملاء المحتملين (Lead Scoring Model)
 *
 * الهدف: إدارة تقييم وترتيب العملاء المحتملين في قاعدة البيانات
 * الميزات: حسابات نقاط ذكية، تحليل سلوك، توقعات تحويل، تعلم آلي
 *
 * @author ERP Team
 * @version 1.0
 * @since 2024
 */

class ModelCrmLeadScoring extends Model {

    /**
     * الحصول على العملاء المحتملين مع النقاط
     */
    public function getLeadsWithScores($data = []) {
        $sql = "
            SELECT
                l.*,
                CONCAT(l.firstname, ' ', l.lastname) as customer_name,
                ls.total_score,
                ls.demographic_score,
                ls.behavioral_score,
                ls.engagement_score,
                ls.company_score,
                ls.source_score,
                ls.priority,
                ls.conversion_probability,
                ls.estimated_value,
                ls.last_calculated,
                u.firstname as assigned_to_name,
                (SELECT MAX(date_created) FROM " . DB_PREFIX . "lead_activity la WHERE la.lead_id = l.lead_id) as last_activity
            FROM " . DB_PREFIX . "lead l
            LEFT JOIN " . DB_PREFIX . "lead_score ls ON (l.lead_id = ls.lead_id)
            LEFT JOIN " . DB_PREFIX . "user u ON (l.assigned_to = u.user_id)
            WHERE 1=1
        ";

        // تطبيق الفلاتر
        if (!empty($data['filter_name'])) {
            $sql .= " AND CONCAT(l.firstname, ' ', l.lastname) LIKE '%" . $this->db->escape($data['filter_name']) . "%'";
        }

        if (!empty($data['filter_score_range'])) {
            $range = explode('-', $data['filter_score_range']);
            if (count($range) == 2) {
                $sql .= " AND ls.total_score BETWEEN '" . (int)$range[0] . "' AND '" . (int)$range[1] . "'";
            }
        }

        if (!empty($data['filter_priority'])) {
            $sql .= " AND ls.priority = '" . $this->db->escape($data['filter_priority']) . "'";
        }

        if (!empty($data['filter_status'])) {
            $sql .= " AND l.status = '" . $this->db->escape($data['filter_status']) . "'";
        }

        if (!empty($data['filter_source'])) {
            $sql .= " AND l.source = '" . $this->db->escape($data['filter_source']) . "'";
        }

        if (!empty($data['filter_assigned_to'])) {
            $sql .= " AND l.assigned_to = '" . (int)$data['filter_assigned_to'] . "'";
        }

        // ترتيب النتائج
        $sort_data = [
            'customer_name',
            'total_score',
            'priority',
            'conversion_probability',
            'estimated_value',
            'last_activity',
            'date_created'
        ];

        if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
            $sql .= " ORDER BY " . $data['sort'];
        } else {
            $sql .= " ORDER BY ls.total_score";
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
     * الحصول على إجمالي عدد العملاء المحتملين
     */
    public function getTotalLeadsWithScores($data = []) {
        $sql = "
            SELECT COUNT(DISTINCT l.lead_id) AS total
            FROM " . DB_PREFIX . "lead l
            LEFT JOIN " . DB_PREFIX . "lead_score ls ON (l.lead_id = ls.lead_id)
            WHERE 1=1
        ";

        // تطبيق نفس الفلاتر
        if (!empty($data['filter_name'])) {
            $sql .= " AND CONCAT(l.firstname, ' ', l.lastname) LIKE '%" . $this->db->escape($data['filter_name']) . "%'";
        }

        if (!empty($data['filter_score_range'])) {
            $range = explode('-', $data['filter_score_range']);
            if (count($range) == 2) {
                $sql .= " AND ls.total_score BETWEEN '" . (int)$range[0] . "' AND '" . (int)$range[1] . "'";
            }
        }

        if (!empty($data['filter_priority'])) {
            $sql .= " AND ls.priority = '" . $this->db->escape($data['filter_priority']) . "'";
        }

        $query = $this->db->query($sql);

        return $query->row['total'];
    }

    /**
     * الحصول على عميل محتمل مع النقاط
     */
    public function getLeadWithScore($lead_id) {
        $query = $this->db->query("
            SELECT
                l.*,
                CONCAT(l.firstname, ' ', l.lastname) as customer_name,
                ls.*,
                u.firstname as assigned_to_name
            FROM " . DB_PREFIX . "lead l
            LEFT JOIN " . DB_PREFIX . "lead_score ls ON (l.lead_id = ls.lead_id)
            LEFT JOIN " . DB_PREFIX . "user u ON (l.assigned_to = u.user_id)
            WHERE l.lead_id = '" . (int)$lead_id . "'
        ");

        return $query->num_rows ? $query->row : false;
    }

    /**
     * حساب نقاط العميل المحتمل
     */
    public function calculateLeadScore($lead_id) {
        $lead = $this->getLead($lead_id);

        if (!$lead) {
            return false;
        }

        // الحصول على قواعد التقييم
        $rules = $this->getScoringRules();

        $scores = [
            'demographic_score' => $this->calculateDemographicScore($lead, $rules),
            'behavioral_score' => $this->calculateBehavioralScore($lead_id, $rules),
            'engagement_score' => $this->calculateEngagementScore($lead_id, $rules),
            'company_score' => $this->calculateCompanyScore($lead, $rules),
            'source_score' => $this->calculateSourceScore($lead, $rules)
        ];

        // حساب النقاط الإجمالية
        $total_score = array_sum($scores);

        // تحديد الأولوية
        $priority = $this->determinePriority($total_score);

        // حساب احتمالية التحويل
        $conversion_probability = $this->calculateConversionProbability($total_score, $scores);

        // تقدير القيمة المتوقعة
        $estimated_value = $this->estimateLeadValue($lead, $total_score);

        // حفظ النتائج
        $this->saveLeadScore($lead_id, array_merge($scores, [
            'total_score' => $total_score,
            'priority' => $priority,
            'conversion_probability' => $conversion_probability,
            'estimated_value' => $estimated_value
        ]));

        return $total_score;
    }

    /**
     * حساب النقاط الديموغرافية
     */
    private function calculateDemographicScore($lead, $rules) {
        $score = 0;

        // العمر
        if (!empty($lead['age'])) {
            if ($lead['age'] >= 25 && $lead['age'] <= 55) {
                $score += $rules['age_optimal'] ?? 10;
            }
        }

        // الموقع الجغرافي
        if (!empty($lead['country'])) {
            $target_countries = ['EG', 'SA', 'AE', 'KW']; // البلدان المستهدفة
            if (in_array($lead['country'], $target_countries)) {
                $score += $rules['location_target'] ?? 15;
            }
        }

        // الجنس (إذا كان مهماً للمنتج)
        if (!empty($lead['gender'])) {
            $score += $rules['gender_match'] ?? 5;
        }

        return min($score, 30); // الحد الأقصى 30 نقطة
    }

    /**
     * حساب النقاط السلوكية
     */
    private function calculateBehavioralScore($lead_id, $rules) {
        $score = 0;

        // عدد زيارات الموقع
        $website_visits = $this->getLeadActivityCount($lead_id, 'website_visit');
        $score += min($website_visits * ($rules['website_visit_points'] ?? 2), 20);

        // تحميل المحتوى
        $downloads = $this->getLeadActivityCount($lead_id, 'content_download');
        $score += min($downloads * ($rules['download_points'] ?? 5), 15);

        // مشاهدة الفيديوهات
        $video_views = $this->getLeadActivityCount($lead_id, 'video_view');
        $score += min($video_views * ($rules['video_points'] ?? 3), 10);

        // طلب عرض أسعار
        $quote_requests = $this->getLeadActivityCount($lead_id, 'quote_request');
        $score += $quote_requests * ($rules['quote_points'] ?? 20);

        return min($score, 40); // الحد الأقصى 40 نقطة
    }

    /**
     * حساب نقاط التفاعل
     */
    private function calculateEngagementScore($lead_id, $rules) {
        $score = 0;

        // فتح الإيميلات
        $email_opens = $this->getLeadActivityCount($lead_id, 'email_open');
        $score += min($email_opens * ($rules['email_open_points'] ?? 1), 10);

        // النقر على الروابط
        $email_clicks = $this->getLeadActivityCount($lead_id, 'email_click');
        $score += min($email_clicks * ($rules['email_click_points'] ?? 3), 15);

        // الرد على الإيميلات
        $email_replies = $this->getLeadActivityCount($lead_id, 'email_reply');
        $score += $email_replies * ($rules['email_reply_points'] ?? 10);

        // المكالمات الهاتفية
        $phone_calls = $this->getLeadActivityCount($lead_id, 'phone_call');
        $score += $phone_calls * ($rules['phone_call_points'] ?? 15);

        // الاجتماعات
        $meetings = $this->getLeadActivityCount($lead_id, 'meeting');
        $score += $meetings * ($rules['meeting_points'] ?? 25);

        return min($score, 50); // الحد الأقصى 50 نقطة
    }

    /**
     * حساب نقاط الشركة
     */
    private function calculateCompanyScore($lead, $rules) {
        $score = 0;

        // حجم الشركة
        if (!empty($lead['company_size'])) {
            $size_scores = [
                'startup' => 5,
                'small' => 10,
                'medium' => 20,
                'large' => 25,
                'enterprise' => 30
            ];
            $score += $size_scores[$lead['company_size']] ?? 0;
        }

        // الصناعة
        if (!empty($lead['industry'])) {
            $target_industries = ['technology', 'retail', 'manufacturing', 'healthcare'];
            if (in_array($lead['industry'], $target_industries)) {
                $score += $rules['industry_match'] ?? 15;
            }
        }

        // الميزانية المتوقعة
        if (!empty($lead['budget'])) {
            if ($lead['budget'] >= 10000) {
                $score += 20;
            } elseif ($lead['budget'] >= 5000) {
                $score += 15;
            } elseif ($lead['budget'] >= 1000) {
                $score += 10;
            }
        }

        return min($score, 35); // الحد الأقصى 35 نقطة
    }

    /**
     * حساب نقاط المصدر
     */
    private function calculateSourceScore($lead, $rules) {
        $source_scores = [
            'referral' => 25,
            'website' => 20,
            'social_media' => 15,
            'email' => 15,
            'advertisement' => 10,
            'phone' => 10,
            'event' => 20,
            'other' => 5
        ];

        return $source_scores[$lead['source']] ?? 5;
    }

    /**
     * تحديد الأولوية
     */
    private function determinePriority($total_score) {
        if ($total_score >= 80) return 'hot';
        if ($total_score >= 60) return 'warm';
        return 'cold';
    }

    /**
     * حساب احتمالية التحويل
     */
    private function calculateConversionProbability($total_score, $scores) {
        // خوارزمية بسيطة لحساب الاحتمالية
        $base_probability = ($total_score / 100) * 100;

        // تعديل حسب التوزيع
        $engagement_weight = ($scores['engagement_score'] / 50) * 0.3;
        $behavioral_weight = ($scores['behavioral_score'] / 40) * 0.25;
        $company_weight = ($scores['company_score'] / 35) * 0.2;

        $adjusted_probability = $base_probability +
                               ($engagement_weight * 100) +
                               ($behavioral_weight * 100) +
                               ($company_weight * 100);

        return min(round($adjusted_probability / 4), 100);
    }

    /**
     * تقدير قيمة العميل المحتمل
     */
    private function estimateLeadValue($lead, $total_score) {
        $base_value = 1000; // قيمة أساسية

        // تعديل حسب النقاط
        $score_multiplier = ($total_score / 100) * 2;

        // تعديل حسب حجم الشركة
        $size_multipliers = [
            'startup' => 0.5,
            'small' => 1,
            'medium' => 2,
            'large' => 5,
            'enterprise' => 10
        ];

        $size_multiplier = $size_multipliers[$lead['company_size']] ?? 1;

        // تعديل حسب الميزانية
        $budget_multiplier = 1;
        if (!empty($lead['budget'])) {
            $budget_multiplier = ($lead['budget'] / 5000);
        }

        $estimated_value = $base_value * $score_multiplier * $size_multiplier * $budget_multiplier;

        return round($estimated_value, 2);
    }

    /**
     * حفظ نقاط العميل المحتمل
     */
    private function saveLeadScore($lead_id, $scores) {
        // التحقق من وجود سجل سابق
        $query = $this->db->query("SELECT score_id FROM " . DB_PREFIX . "lead_score WHERE lead_id = '" . (int)$lead_id . "'");

        if ($query->num_rows) {
            // تحديث السجل الموجود
            $this->db->query("
                UPDATE " . DB_PREFIX . "lead_score SET
                    demographic_score = '" . (float)$scores['demographic_score'] . "',
                    behavioral_score = '" . (float)$scores['behavioral_score'] . "',
                    engagement_score = '" . (float)$scores['engagement_score'] . "',
                    company_score = '" . (float)$scores['company_score'] . "',
                    source_score = '" . (float)$scores['source_score'] . "',
                    total_score = '" . (float)$scores['total_score'] . "',
                    priority = '" . $this->db->escape($scores['priority']) . "',
                    conversion_probability = '" . (float)$scores['conversion_probability'] . "',
                    estimated_value = '" . (float)$scores['estimated_value'] . "',
                    last_calculated = NOW()
                WHERE lead_id = '" . (int)$lead_id . "'
            ");
        } else {
            // إنشاء سجل جديد
            $this->db->query("
                INSERT INTO " . DB_PREFIX . "lead_score SET
                    lead_id = '" . (int)$lead_id . "',
                    demographic_score = '" . (float)$scores['demographic_score'] . "',
                    behavioral_score = '" . (float)$scores['behavioral_score'] . "',
                    engagement_score = '" . (float)$scores['engagement_score'] . "',
                    company_score = '" . (float)$scores['company_score'] . "',
                    source_score = '" . (float)$scores['source_score'] . "',
                    total_score = '" . (float)$scores['total_score'] . "',
                    priority = '" . $this->db->escape($scores['priority']) . "',
                    conversion_probability = '" . (float)$scores['conversion_probability'] . "',
                    estimated_value = '" . (float)$scores['estimated_value'] . "',
                    last_calculated = NOW(),
                    date_created = NOW()
            ");
        }
    }

    /**
     * دوال مساعدة
     */
    private function getLead($lead_id) {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "lead WHERE lead_id = '" . (int)$lead_id . "'");
        return $query->num_rows ? $query->row : false;
    }

    private function getLeadActivityCount($lead_id, $activity_type) {
        $query = $this->db->query("
            SELECT COUNT(*) as total
            FROM " . DB_PREFIX . "lead_activity
            WHERE lead_id = '" . (int)$lead_id . "' AND activity_type = '" . $this->db->escape($activity_type) . "'
        ");
        return $query->row['total'];
    }

    /**
     * إعادة حساب النقاط
     */
    public function recalculateScore($lead_id) {
        return $this->calculateLeadScore($lead_id);
    }

    /**
     * الحصول على قواعد التقييم
     */
    public function getScoringRules() {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "lead_scoring_rules ORDER BY category, rule_name");

        $rules = [];
        foreach ($query->rows as $row) {
            $rules[$row['rule_key']] = $row['rule_value'];
        }

        return $rules;
    }

    /**
     * تحديث قواعد التقييم
     */
    public function updateScoringRules($rules_data) {
        foreach ($rules_data as $rule_key => $rule_value) {
            $this->db->query("
                INSERT INTO " . DB_PREFIX . "lead_scoring_rules
                SET rule_key = '" . $this->db->escape($rule_key) . "',
                    rule_value = '" . (float)$rule_value . "',
                    date_modified = NOW()
                ON DUPLICATE KEY UPDATE
                    rule_value = '" . (float)$rule_value . "',
                    date_modified = NOW()
            ");
        }
    }

    /**
     * إحصائيات سريعة
     */
    public function getTotalLeads() {
        $query = $this->db->query("SELECT COUNT(*) as total FROM " . DB_PREFIX . "lead WHERE status != 'converted'");
        return $query->row['total'];
    }

    public function getHotLeads() {
        $query = $this->db->query("
            SELECT COUNT(*) as total
            FROM " . DB_PREFIX . "lead l
            LEFT JOIN " . DB_PREFIX . "lead_score ls ON l.lead_id = ls.lead_id
            WHERE ls.priority = 'hot' AND l.status != 'converted'
        ");
        return $query->row['total'];
    }

    public function getAverageScore() {
        $query = $this->db->query("SELECT AVG(total_score) as avg_score FROM " . DB_PREFIX . "lead_score");
        return round($query->row['avg_score'], 1);
    }

    public function getConversionRate() {
        $total_query = $this->db->query("SELECT COUNT(*) as total FROM " . DB_PREFIX . "lead");
        $converted_query = $this->db->query("SELECT COUNT(*) as total FROM " . DB_PREFIX . "lead WHERE status = 'converted'");

        $total = $total_query->row['total'];
        $converted = $converted_query->row['total'];

        return $total > 0 ? round(($converted / $total) * 100, 1) : 0;
    }

    public function getMonthlyConversions() {
        $query = $this->db->query("
            SELECT COUNT(*) as total
            FROM " . DB_PREFIX . "lead
            WHERE status = 'converted'
            AND MONTH(date_modified) = MONTH(CURDATE())
            AND YEAR(date_modified) = YEAR(CURDATE())
        ");
        return $query->row['total'];
    }

    public function getPipelineValue() {
        $query = $this->db->query("
            SELECT SUM(estimated_value) as total
            FROM " . DB_PREFIX . "lead_score ls
            LEFT JOIN " . DB_PREFIX . "lead l ON ls.lead_id = l.lead_id
            WHERE l.status NOT IN ('converted', 'lost')
        ");
        return $query->row['total'] ?? 0;
    }

    /**
     * الحصول على بيانات زيارات الموقع للعميل المحتمل
     */
    public function getLeadWebsiteVisits($lead_id) {
        $query = $this->db->query("
            SELECT COUNT(*) as visit_count
            FROM " . DB_PREFIX . "crm_lead_activity
            WHERE lead_id = '" . (int)$lead_id . "'
            AND activity_type = 'website_visit'
        ");

        return $query->row['visit_count'];
    }

    /**
     * الحصول على مشاهدات الصفحات للعميل المحتمل
     */
    public function getLeadPageViews($lead_id) {
        $query = $this->db->query("
            SELECT COUNT(*) as page_views
            FROM " . DB_PREFIX . "crm_lead_activity
            WHERE lead_id = '" . (int)$lead_id . "'
            AND activity_type = 'page_view'
        ");

        return $query->row['page_views'];
    }

    /**
     * الحصول على الوقت المقضي في الموقع
     */
    public function getLeadTimeOnSite($lead_id) {
        $query = $this->db->query("
            SELECT SUM(CAST(JSON_EXTRACT(activity_data, '$.time_spent') AS UNSIGNED)) as total_time
            FROM " . DB_PREFIX . "crm_lead_activity
            WHERE lead_id = '" . (int)$lead_id . "'
            AND activity_type = 'website_visit'
            AND JSON_EXTRACT(activity_data, '$.time_spent') IS NOT NULL
        ");

        return $query->row['total_time'] ?: 0;
    }

    /**
     * الحصول على التحميلات للعميل المحتمل
     */
    public function getLeadDownloads($lead_id) {
        $query = $this->db->query("
            SELECT COUNT(*) as downloads
            FROM " . DB_PREFIX . "crm_lead_activity
            WHERE lead_id = '" . (int)$lead_id . "'
            AND activity_type = 'download'
        ");

        return $query->row['downloads'];
    }

    /**
     * الحصول على إرسال النماذج للعميل المحتمل
     */
    public function getLeadFormSubmissions($lead_id) {
        $query = $this->db->query("
            SELECT COUNT(*) as form_submissions
            FROM " . DB_PREFIX . "crm_lead_activity
            WHERE lead_id = '" . (int)$lead_id . "'
            AND activity_type = 'form_submission'
        ");

        return $query->row['form_submissions'];
    }

    /**
     * الحصول على فتح رسائل البريد الإلكتروني
     */
    public function getLeadEmailOpens($lead_id) {
        $query = $this->db->query("
            SELECT COUNT(*) as email_opens
            FROM " . DB_PREFIX . "crm_lead_activity
            WHERE lead_id = '" . (int)$lead_id . "'
            AND activity_type = 'email_open'
        ");

        return $query->row['email_opens'];
    }

    /**
     * الحصول على النقر على روابط البريد الإلكتروني
     */
    public function getLeadEmailClicks($lead_id) {
        $query = $this->db->query("
            SELECT COUNT(*) as email_clicks
            FROM " . DB_PREFIX . "crm_lead_activity
            WHERE lead_id = '" . (int)$lead_id . "'
            AND activity_type = 'email_click'
        ");

        return $query->row['email_clicks'];
    }

    /**
     * الحصول على المشاركات الاجتماعية
     */
    public function getLeadSocialShares($lead_id) {
        $query = $this->db->query("
            SELECT COUNT(*) as social_shares
            FROM " . DB_PREFIX . "crm_lead_activity
            WHERE lead_id = '" . (int)$lead_id . "'
            AND activity_type IN ('social_share', 'social_like', 'social_comment')
        ");

        return $query->row['social_shares'];
    }

    /**
     * الحصول على حضور الفعاليات
     */
    public function getLeadEventAttendance($lead_id) {
        $query = $this->db->query("
            SELECT COUNT(*) as event_attendance
            FROM " . DB_PREFIX . "crm_lead_activity
            WHERE lead_id = '" . (int)$lead_id . "'
            AND activity_type = 'event_attendance'
        ");

        return $query->row['event_attendance'];
    }

    /**
     * الحصول على المشاركة في الندوات الإلكترونية
     */
    public function getLeadWebinarParticipation($lead_id) {
        $query = $this->db->query("
            SELECT COUNT(*) as webinar_participation
            FROM " . DB_PREFIX . "crm_lead_activity
            WHERE lead_id = '" . (int)$lead_id . "'
            AND activity_type = 'webinar_participation'
        ");

        return $query->row['webinar_participation'];
    }

    /**
     * الحصول على الردود على الاستطلاعات
     */
    public function getLeadSurveyResponses($lead_id) {
        $query = $this->db->query("
            SELECT COUNT(*) as survey_responses
            FROM " . DB_PREFIX . "crm_lead_activity
            WHERE lead_id = '" . (int)$lead_id . "'
            AND activity_type = 'survey_response'
        ");

        return $query->row['survey_responses'];
    }

    /**
     * الحصول على أداء الحملة
     */
    public function getCampaignPerformance($campaign_id) {
        $query = $this->db->query("
            SELECT
                COUNT(*) as total_leads,
                COUNT(CASE WHEN l.status = 'converted' THEN 1 END) as converted_leads,
                (COUNT(CASE WHEN l.status = 'converted' THEN 1 END) / COUNT(*) * 100) as conversion_rate,
                AVG(ls.total_score) as avg_score
            FROM " . DB_PREFIX . "crm_lead l
            LEFT JOIN " . DB_PREFIX . "crm_lead_score ls ON (l.lead_id = ls.lead_id)
            WHERE l.campaign_id = '" . (int)$campaign_id . "'
        ");

        return $query->row;
    }

    /**
     * الحصول على إحصائيات التقييم
     */
    public function getScoringStatistics() {
        $query = $this->db->query("
            SELECT
                COUNT(*) as total_leads,
                AVG(total_score) as avg_total_score,
                AVG(demographic_score) as avg_demographic_score,
                AVG(behavioral_score) as avg_behavioral_score,
                AVG(engagement_score) as avg_engagement_score,
                AVG(company_score) as avg_company_score,
                AVG(source_score) as avg_source_score,
                COUNT(CASE WHEN total_score >= 90 THEN 1 END) as hot_leads,
                COUNT(CASE WHEN total_score >= 70 AND total_score < 90 THEN 1 END) as warm_leads,
                COUNT(CASE WHEN total_score < 70 THEN 1 END) as cold_leads
            FROM " . DB_PREFIX . "crm_lead_score
        ");

        return $query->row;
    }

    /**
     * الحصول على توزيع النقاط
     */
    public function getScoreDistribution() {
        $query = $this->db->query("
            SELECT
                CASE
                    WHEN total_score >= 90 THEN 'Hot (90-100)'
                    WHEN total_score >= 80 THEN 'Very Warm (80-89)'
                    WHEN total_score >= 70 THEN 'Warm (70-79)'
                    WHEN total_score >= 60 THEN 'Moderate (60-69)'
                    WHEN total_score >= 50 THEN 'Cool (50-59)'
                    WHEN total_score >= 40 THEN 'Cold (40-49)'
                    ELSE 'Very Cold (0-39)'
                END as score_range,
                COUNT(*) as lead_count,
                (COUNT(*) * 100.0 / (SELECT COUNT(*) FROM " . DB_PREFIX . "crm_lead_score)) as percentage
            FROM " . DB_PREFIX . "crm_lead_score
            GROUP BY score_range
            ORDER BY MIN(total_score) DESC
        ");

        return $query->rows;
    }

    /**
     * الحصول على أفضل المصادر
     */
    public function getTopSources() {
        $query = $this->db->query("
            SELECT
                l.source,
                COUNT(*) as lead_count,
                AVG(ls.total_score) as avg_score,
                COUNT(CASE WHEN l.status = 'converted' THEN 1 END) as converted_count,
                (COUNT(CASE WHEN l.status = 'converted' THEN 1 END) / COUNT(*) * 100) as conversion_rate
            FROM " . DB_PREFIX . "crm_lead l
            LEFT JOIN " . DB_PREFIX . "crm_lead_score ls ON (l.lead_id = ls.lead_id)
            GROUP BY l.source
            ORDER BY avg_score DESC, conversion_rate DESC
            LIMIT 10
        ");

        return $query->rows;
    }

    /**
     * الحصول على اتجاه النقاط
     */
    public function getScoreTrend($days = 30) {
        $query = $this->db->query("
            SELECT
                DATE(calculated_at) as score_date,
                AVG(total_score) as avg_score,
                COUNT(*) as lead_count
            FROM " . DB_PREFIX . "crm_lead_score
            WHERE calculated_at >= DATE_SUB(NOW(), INTERVAL " . (int)$days . " DAY)
            GROUP BY DATE(calculated_at)
            ORDER BY score_date ASC
        ");

        return $query->rows;
    }

    /**
     * الحصول على العملاء المحتملين عالي الأولوية
     */
    public function getHighPriorityLeads($limit = 10) {
        $query = $this->db->query("
            SELECT l.*, ls.total_score, u.firstname, u.lastname
            FROM " . DB_PREFIX . "crm_lead l
            LEFT JOIN " . DB_PREFIX . "crm_lead_score ls ON (l.lead_id = ls.lead_id)
            LEFT JOIN " . DB_PREFIX . "user u ON (l.assigned_to = u.user_id)
            WHERE l.status NOT IN ('converted', 'lost', 'closed')
            AND ls.total_score >= 80
            ORDER BY ls.total_score DESC, l.estimated_value DESC
            LIMIT " . (int)$limit
        );

        return $query->rows;
    }

    /**
     * الحصول على العملاء المحتملين المتوقفين
     */
    public function getStalledLeads($days = 7) {
        $query = $this->db->query("
            SELECT l.*, ls.total_score, u.firstname, u.lastname,
                   DATEDIFF(NOW(), l.date_modified) as days_stalled
            FROM " . DB_PREFIX . "crm_lead l
            LEFT JOIN " . DB_PREFIX . "crm_lead_score ls ON (l.lead_id = ls.lead_id)
            LEFT JOIN " . DB_PREFIX . "user u ON (l.assigned_to = u.user_id)
            WHERE l.status NOT IN ('converted', 'lost', 'closed')
            AND l.date_modified < DATE_SUB(NOW(), INTERVAL " . (int)$days . " DAY)
            ORDER BY days_stalled DESC, ls.total_score DESC
        ");

        return $query->rows;
    }

    /**
     * تحويل العميل المحتمل إلى عميل
     */
    public function convertLeadToCustomer($lead_id, $customer_data) {
        // الحصول على بيانات العميل المحتمل
        $lead = $this->getLeadWithScore($lead_id);

        if (!$lead) {
            return false;
        }

        // إضافة العميل الجديد
        $this->db->query("
            INSERT INTO " . DB_PREFIX . "customer SET
            firstname = '" . $this->db->escape($customer_data['firstname']) . "',
            lastname = '" . $this->db->escape($customer_data['lastname']) . "',
            email = '" . $this->db->escape($lead['email']) . "',
            telephone = '" . $this->db->escape($lead['phone']) . "',
            company = '" . $this->db->escape($lead['company']) . "',
            status = '1',
            approved = '1',
            date_added = NOW()
        ");

        $customer_id = $this->db->getLastId();

        // تحديث حالة العميل المحتمل
        $this->db->query("
            UPDATE " . DB_PREFIX . "crm_lead SET
            status = 'converted',
            customer_id = '" . (int)$customer_id . "',
            conversion_date = NOW(),
            date_modified = NOW()
            WHERE lead_id = '" . (int)$lead_id . "'
        ");

        return $customer_id;
    }
}
