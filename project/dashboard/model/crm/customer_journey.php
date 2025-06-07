<?php
/**
 * نموذج رحلة العميل (Customer Journey Model)
 *
 * الهدف: إدارة وتتبع رحلة العميل عبر جميع نقاط اللمس والمراحل
 * الميزات: تتبع شامل، تحليل سلوك، خريطة تفاعلية، صحة الرحلة
 *
 * @author ERP Team
 * @version 1.0
 * @since 2024
 */

class ModelCrmCustomerJourney extends Model {

    /**
     * الحصول على رحلات العملاء مع الفلاتر
     */
    public function getCustomerJourneys($data = []) {
        $sql = "
            SELECT
                cj.*,
                CONCAT(c.firstname, ' ', c.lastname) as customer_name,
                c.email,
                c.telephone as phone,
                u.firstname as assigned_to_name,
                (SELECT COUNT(*) FROM " . DB_PREFIX . "customer_journey_touchpoint cjt WHERE cjt.journey_id = cj.journey_id) as total_touchpoints,
                (SELECT MAX(date_created) FROM " . DB_PREFIX . "customer_journey_touchpoint cjt WHERE cjt.journey_id = cj.journey_id) as last_activity,
                (SELECT SUM(o.total) FROM " . DB_PREFIX . "order o WHERE o.customer_id = cj.customer_id AND o.order_status_id > 0) as total_value
            FROM " . DB_PREFIX . "customer_journey cj
            LEFT JOIN " . DB_PREFIX . "customer c ON (cj.customer_id = c.customer_id)
            LEFT JOIN " . DB_PREFIX . "user u ON (cj.assigned_to = u.user_id)
            WHERE 1=1
        ";

        // تطبيق الفلاتر
        if (!empty($data['filter_customer'])) {
            $sql .= " AND CONCAT(c.firstname, ' ', c.lastname) LIKE '%" . $this->db->escape($data['filter_customer']) . "%'";
        }

        if (!empty($data['filter_stage'])) {
            $sql .= " AND cj.current_stage = '" . $this->db->escape($data['filter_stage']) . "'";
        }

        if (!empty($data['filter_health'])) {
            $sql .= " AND cj.journey_health = '" . $this->db->escape($data['filter_health']) . "'";
        }

        if (!empty($data['filter_touchpoint'])) {
            $sql .= " AND cj.first_touchpoint = '" . $this->db->escape($data['filter_touchpoint']) . "'";
        }

        if (!empty($data['filter_assigned_to'])) {
            $sql .= " AND cj.assigned_to = '" . (int)$data['filter_assigned_to'] . "'";
        }

        if (!empty($data['filter_date_from'])) {
            $sql .= " AND DATE(cj.journey_start) >= '" . $this->db->escape($data['filter_date_from']) . "'";
        }

        if (!empty($data['filter_date_to'])) {
            $sql .= " AND DATE(cj.journey_start) <= '" . $this->db->escape($data['filter_date_to']) . "'";
        }

        // ترتيب النتائج
        $sort_data = [
            'customer_name',
            'current_stage',
            'journey_health',
            'total_touchpoints',
            'total_value',
            'last_activity',
            'journey_start'
        ];

        if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
            $sql .= " ORDER BY " . $data['sort'];
        } else {
            $sql .= " ORDER BY cj.last_activity";
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

        // حساب احتمالية التحويل لكل رحلة
        foreach ($query->rows as &$row) {
            $row['conversion_probability'] = $this->calculateConversionProbability($row['journey_id']);
        }

        return $query->rows;
    }

    /**
     * الحصول على إجمالي عدد رحلات العملاء
     */
    public function getTotalCustomerJourneys($data = []) {
        $sql = "
            SELECT COUNT(DISTINCT cj.journey_id) AS total
            FROM " . DB_PREFIX . "customer_journey cj
            LEFT JOIN " . DB_PREFIX . "customer c ON (cj.customer_id = c.customer_id)
            WHERE 1=1
        ";

        // تطبيق نفس الفلاتر
        if (!empty($data['filter_customer'])) {
            $sql .= " AND CONCAT(c.firstname, ' ', c.lastname) LIKE '%" . $this->db->escape($data['filter_customer']) . "%'";
        }

        if (!empty($data['filter_stage'])) {
            $sql .= " AND cj.current_stage = '" . $this->db->escape($data['filter_stage']) . "'";
        }

        if (!empty($data['filter_health'])) {
            $sql .= " AND cj.journey_health = '" . $this->db->escape($data['filter_health']) . "'";
        }

        $query = $this->db->query($sql);

        return $query->row['total'];
    }

    /**
     * الحصول على رحلة عميل محددة
     */
    public function getCustomerJourney($journey_id) {
        $query = $this->db->query("
            SELECT
                cj.*,
                CONCAT(c.firstname, ' ', c.lastname) as customer_name,
                c.email,
                c.telephone as phone,
                c.company,
                u.firstname as assigned_to_name,
                (SELECT COUNT(*) FROM " . DB_PREFIX . "customer_journey_touchpoint cjt WHERE cjt.journey_id = cj.journey_id) as total_touchpoints,
                (SELECT MAX(date_created) FROM " . DB_PREFIX . "customer_journey_touchpoint cjt WHERE cjt.journey_id = cj.journey_id) as last_activity,
                (SELECT SUM(o.total) FROM " . DB_PREFIX . "order o WHERE o.customer_id = cj.customer_id AND o.order_status_id > 0) as total_value
            FROM " . DB_PREFIX . "customer_journey cj
            LEFT JOIN " . DB_PREFIX . "customer c ON (cj.customer_id = c.customer_id)
            LEFT JOIN " . DB_PREFIX . "user u ON (cj.assigned_to = u.user_id)
            WHERE cj.journey_id = '" . (int)$journey_id . "'
        ");

        if ($query->num_rows) {
            $journey = $query->row;
            $journey['conversion_probability'] = $this->calculateConversionProbability($journey_id);
            return $journey;
        }

        return false;
    }

    /**
     * الحصول على نقاط اللمس لرحلة معينة
     */
    public function getJourneyTouchpoints($journey_id) {
        $query = $this->db->query("
            SELECT
                cjt.*,
                u.firstname as created_by_name
            FROM " . DB_PREFIX . "customer_journey_touchpoint cjt
            LEFT JOIN " . DB_PREFIX . "user u ON (cjt.created_by = u.user_id)
            WHERE cjt.journey_id = '" . (int)$journey_id . "'
            ORDER BY cjt.date_created ASC
        ");

        return $query->rows;
    }

    /**
     * الحصول على مراحل الرحلة
     */
    public function getJourneyStages($journey_id) {
        $query = $this->db->query("
            SELECT
                cjs.*,
                u.firstname as updated_by_name
            FROM " . DB_PREFIX . "customer_journey_stage cjs
            LEFT JOIN " . DB_PREFIX . "user u ON (cjs.updated_by = u.user_id)
            WHERE cjs.journey_id = '" . (int)$journey_id . "'
            ORDER BY cjs.stage_order ASC
        ");

        return $query->rows;
    }

    /**
     * إنشاء رحلة عميل جديدة
     */
    public function createCustomerJourney($customer_id, $first_touchpoint, $source = '') {
        // التحقق من وجود رحلة نشطة للعميل
        $existing_query = $this->db->query("
            SELECT journey_id
            FROM " . DB_PREFIX . "customer_journey
            WHERE customer_id = '" . (int)$customer_id . "'
            AND current_stage NOT IN ('converted', 'lost')
        ");

        if ($existing_query->num_rows) {
            return $existing_query->row['journey_id'];
        }

        // إنشاء رحلة جديدة
        $this->db->query("
            INSERT INTO " . DB_PREFIX . "customer_journey SET
                customer_id = '" . (int)$customer_id . "',
                first_touchpoint = '" . $this->db->escape($first_touchpoint) . "',
                current_stage = 'awareness',
                journey_health = 'good',
                conversion_probability = 0,
                journey_start = NOW(),
                date_created = NOW()
        ");

        $journey_id = $this->db->getLastId();

        // إنشاء المراحل الافتراضية
        $this->createDefaultStages($journey_id);

        // إضافة نقطة اللمس الأولى
        $this->addTouchpoint($journey_id, $first_touchpoint, 'first_contact', 'العميل بدأ رحلته معنا');

        return $journey_id;
    }

    /**
     * إضافة نقطة لمس جديدة
     */
    public function addTouchpoint($journey_id, $touchpoint_type, $activity_type, $description, $engagement_value = 1) {
        $this->db->query("
            INSERT INTO " . DB_PREFIX . "customer_journey_touchpoint SET
                journey_id = '" . (int)$journey_id . "',
                touchpoint_type = '" . $this->db->escape($touchpoint_type) . "',
                activity_type = '" . $this->db->escape($activity_type) . "',
                description = '" . $this->db->escape($description) . "',
                engagement_value = '" . (int)$engagement_value . "',
                created_by = '" . (int)$this->user->getId() . "',
                date_created = NOW()
        ");

        $touchpoint_id = $this->db->getLastId();

        // تحديث صحة الرحلة
        $this->updateJourneyHealth($journey_id);

        return $touchpoint_id;
    }

    /**
     * تحديث مرحلة الرحلة
     */
    public function updateJourneyStage($journey_id, $new_stage) {
        // تحديث المرحلة الحالية
        $this->db->query("
            UPDATE " . DB_PREFIX . "customer_journey
            SET current_stage = '" . $this->db->escape($new_stage) . "',
                date_modified = NOW()
            WHERE journey_id = '" . (int)$journey_id . "'
        ");

        // تحديث حالة المرحلة في جدول المراحل
        $this->db->query("
            UPDATE " . DB_PREFIX . "customer_journey_stage
            SET status = 'completed',
                completed_date = NOW(),
                updated_by = '" . (int)$this->user->getId() . "'
            WHERE journey_id = '" . (int)$journey_id . "'
            AND stage_name = '" . $this->db->escape($new_stage) . "'
        ");

        // تحديث احتمالية التحويل
        $this->updateConversionProbability($journey_id);

        // تحديث صحة الرحلة
        $this->updateJourneyHealth($journey_id);
    }

    /**
     * حساب احتمالية التحويل
     */
    private function calculateConversionProbability($journey_id) {
        $journey = $this->getBasicJourneyInfo($journey_id);
        if (!$journey) return 0;

        $score = 0;

        // نقاط المرحلة الحالية
        $stage_scores = [
            'awareness' => 10,
            'interest' => 25,
            'consideration' => 50,
            'purchase' => 90,
            'retention' => 95,
            'advocacy' => 100
        ];
        $score += $stage_scores[$journey['current_stage']] ?? 0;

        // نقاط عدد نقاط اللمس
        $touchpoint_count = $this->getTouchpointCount($journey_id);
        $score += min($touchpoint_count * 2, 20);

        // نقاط التفاعل
        $engagement_score = $this->getEngagementScore($journey_id);
        $score += min($engagement_score, 30);

        // نقاط الوقت في الرحلة
        $days_in_journey = (time() - strtotime($journey['journey_start'])) / (24 * 60 * 60);
        if ($days_in_journey <= 30) {
            $score += 10; // رحلة حديثة
        } elseif ($days_in_journey > 90) {
            $score -= 10; // رحلة قديمة
        }

        return min(100, max(0, $score));
    }

    /**
     * تحديث صحة الرحلة
     */
    private function updateJourneyHealth($journey_id) {
        $touchpoint_count = $this->getTouchpointCount($journey_id);
        $engagement_score = $this->getEngagementScore($journey_id);
        $days_since_last_activity = $this->getDaysSinceLastActivity($journey_id);

        $health = 'good'; // افتراضي

        if ($engagement_score >= 80 && $days_since_last_activity <= 7) {
            $health = 'excellent';
        } elseif ($engagement_score >= 60 && $days_since_last_activity <= 14) {
            $health = 'good';
        } elseif ($engagement_score >= 40 && $days_since_last_activity <= 30) {
            $health = 'fair';
        } else {
            $health = 'poor';
        }

        $this->db->query("
            UPDATE " . DB_PREFIX . "customer_journey
            SET journey_health = '" . $this->db->escape($health) . "'
            WHERE journey_id = '" . (int)$journey_id . "'
        ");
    }

    /**
     * إنشاء المراحل الافتراضية
     */
    private function createDefaultStages($journey_id) {
        $stages = [
            ['awareness', 'الوعي', 1],
            ['interest', 'الاهتمام', 2],
            ['consideration', 'الاعتبار', 3],
            ['purchase', 'الشراء', 4],
            ['retention', 'الاحتفاظ', 5],
            ['advocacy', 'الدعوة', 6]
        ];

        foreach ($stages as $stage) {
            $this->db->query("
                INSERT INTO " . DB_PREFIX . "customer_journey_stage SET
                    journey_id = '" . (int)$journey_id . "',
                    stage_name = '" . $this->db->escape($stage[0]) . "',
                    stage_title = '" . $this->db->escape($stage[1]) . "',
                    stage_order = '" . (int)$stage[2] . "',
                    status = '" . ($stage[2] == 1 ? 'active' : 'pending') . "',
                    date_created = NOW()
            ");
        }
    }

    /**
     * دوال مساعدة
     */
    private function getBasicJourneyInfo($journey_id) {
        $query = $this->db->query("
            SELECT current_stage, journey_start
            FROM " . DB_PREFIX . "customer_journey
            WHERE journey_id = '" . (int)$journey_id . "'
        ");
        return $query->num_rows ? $query->row : false;
    }

    private function getTouchpointCount($journey_id) {
        $query = $this->db->query("
            SELECT COUNT(*) as total
            FROM " . DB_PREFIX . "customer_journey_touchpoint
            WHERE journey_id = '" . (int)$journey_id . "'
        ");
        return $query->row['total'];
    }

    private function getEngagementScore($journey_id) {
        $query = $this->db->query("
            SELECT SUM(engagement_value) as total
            FROM " . DB_PREFIX . "customer_journey_touchpoint
            WHERE journey_id = '" . (int)$journey_id . "'
        ");
        return $query->row['total'] ?? 0;
    }

    private function getDaysSinceLastActivity($journey_id) {
        $query = $this->db->query("
            SELECT MAX(date_created) as last_activity
            FROM " . DB_PREFIX . "customer_journey_touchpoint
            WHERE journey_id = '" . (int)$journey_id . "'
        ");

        if ($query->num_rows && $query->row['last_activity']) {
            return (time() - strtotime($query->row['last_activity'])) / (24 * 60 * 60);
        }

        return 999; // رقم كبير إذا لم يكن هناك نشاط
    }

    private function updateConversionProbability($journey_id) {
        $probability = $this->calculateConversionProbability($journey_id);

        $this->db->query("
            UPDATE " . DB_PREFIX . "customer_journey
            SET conversion_probability = '" . (float)$probability . "'
            WHERE journey_id = '" . (int)$journey_id . "'
        ");
    }

    /**
     * إحصائيات سريعة
     */
    public function getTotalJourneys() {
        $query = $this->db->query("SELECT COUNT(*) as total FROM " . DB_PREFIX . "customer_journey");
        return $query->row['total'];
    }

    public function getActiveJourneys() {
        $query = $this->db->query("
            SELECT COUNT(*) as total
            FROM " . DB_PREFIX . "customer_journey
            WHERE current_stage NOT IN ('converted', 'lost')
        ");
        return $query->row['total'];
    }

    public function getAverageDuration() {
        $query = $this->db->query("
            SELECT AVG(DATEDIFF(COALESCE(date_modified, CURDATE()), journey_start)) as avg_duration
            FROM " . DB_PREFIX . "customer_journey
        ");
        return round($query->row['avg_duration'], 1);
    }

    public function getConversionRate() {
        $total_query = $this->db->query("SELECT COUNT(*) as total FROM " . DB_PREFIX . "customer_journey");
        $converted_query = $this->db->query("
            SELECT COUNT(*) as total
            FROM " . DB_PREFIX . "customer_journey
            WHERE current_stage = 'converted'
        ");

        $total = $total_query->row['total'];
        $converted = $converted_query->row['total'];

        return $total > 0 ? round(($converted / $total) * 100, 1) : 0;
    }

    public function getTopTouchpoint() {
        $query = $this->db->query("
            SELECT touchpoint_type, COUNT(*) as count
            FROM " . DB_PREFIX . "customer_journey_touchpoint
            GROUP BY touchpoint_type
            ORDER BY count DESC
            LIMIT 1
        ");
        return $query->num_rows ? $query->row['touchpoint_type'] : 'website';
    }

    public function getHealthDistribution() {
        $query = $this->db->query("
            SELECT
                journey_health,
                COUNT(*) as count
            FROM " . DB_PREFIX . "customer_journey
            GROUP BY journey_health
        ");

        $distribution = [];
        foreach ($query->rows as $row) {
            $distribution[$row['journey_health']] = $row['count'];
        }

        return $distribution;
    }

    /**
     * تحليلات متقدمة
     */
    public function getStageConversionRates() {
        $stages = ['awareness', 'interest', 'consideration', 'purchase', 'retention', 'advocacy'];
        $conversion_rates = [];

        for ($i = 0; $i < count($stages) - 1; $i++) {
            $current_stage = $stages[$i];
            $next_stage = $stages[$i + 1];

            $current_count_query = $this->db->query("
                SELECT COUNT(*) as total
                FROM " . DB_PREFIX . "customer_journey_stage
                WHERE stage_name = '" . $current_stage . "' AND status = 'completed'
            ");

            $next_count_query = $this->db->query("
                SELECT COUNT(*) as total
                FROM " . DB_PREFIX . "customer_journey_stage
                WHERE stage_name = '" . $next_stage . "' AND status = 'completed'
            ");

            $current_count = $current_count_query->row['total'];
            $next_count = $next_count_query->row['total'];

            $conversion_rate = $current_count > 0 ? ($next_count / $current_count) * 100 : 0;

            $conversion_rates[] = [
                'from_stage' => $current_stage,
                'to_stage' => $next_stage,
                'conversion_rate' => round($conversion_rate, 1)
            ];
        }

        return $conversion_rates;
    }

    public function getTouchpointEffectiveness() {
        $query = $this->db->query("
            SELECT
                cjt.touchpoint_type,
                COUNT(*) as total_touchpoints,
                AVG(cjt.engagement_value) as avg_engagement,
                COUNT(DISTINCT cj.journey_id) as unique_journeys,
                SUM(CASE WHEN cj.current_stage = 'converted' THEN 1 ELSE 0 END) as conversions
            FROM " . DB_PREFIX . "customer_journey_touchpoint cjt
            LEFT JOIN " . DB_PREFIX . "customer_journey cj ON cjt.journey_id = cj.journey_id
            GROUP BY cjt.touchpoint_type
            ORDER BY avg_engagement DESC
        ");

        return $query->rows;
    }

    public function getJourneyDurationAnalysis() {
        $query = $this->db->query("
            SELECT
                current_stage,
                AVG(DATEDIFF(COALESCE(date_modified, CURDATE()), journey_start)) as avg_duration,
                MIN(DATEDIFF(COALESCE(date_modified, CURDATE()), journey_start)) as min_duration,
                MAX(DATEDIFF(COALESCE(date_modified, CURDATE()), journey_start)) as max_duration
            FROM " . DB_PREFIX . "customer_journey
            GROUP BY current_stage
        ");

        return $query->rows;
    }

    public function getDropOffPoints() {
        $query = $this->db->query("
            SELECT
                current_stage,
                COUNT(*) as stuck_count,
                AVG(DATEDIFF(CURDATE(), journey_start)) as avg_days_stuck
            FROM " . DB_PREFIX . "customer_journey
            WHERE current_stage NOT IN ('converted', 'lost')
            AND DATEDIFF(CURDATE(), journey_start) > 30
            GROUP BY current_stage
            ORDER BY stuck_count DESC
        ");

        return $query->rows;
    }

    /**
     * الحصول على إحصائيات الرحلات
     */
    public function getJourneyStatistics() {
        $query = $this->db->query("
            SELECT
                COUNT(*) as total_journeys,
                COUNT(CASE WHEN status = 'active' THEN 1 END) as active_journeys,
                COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_journeys,
                COUNT(CASE WHEN status = 'stalled' THEN 1 END) as stalled_journeys,
                AVG(health_score) as avg_health_score,
                AVG(conversion_probability) as avg_conversion_probability,
                SUM(estimated_value) as total_estimated_value
            FROM " . DB_PREFIX . "crm_customer_journey
        ");

        return $query->row;
    }

    /**
     * الحصول على إجمالي الرحلات النشطة
     */
    public function getTotalActiveJourneys() {
        $query = $this->db->query("
            SELECT COUNT(*) as total
            FROM " . DB_PREFIX . "crm_customer_journey
            WHERE status = 'active'
        ");

        return $query->row['total'];
    }

    /**
     * الحصول على متوسط مدة الرحلة
     */
    public function getAverageJourneyTime() {
        $query = $this->db->query("
            SELECT AVG(DATEDIFF(completion_date, start_date)) as avg_days
            FROM " . DB_PREFIX . "crm_customer_journey
            WHERE status = 'completed'
            AND completion_date IS NOT NULL
        ");

        return $query->row['avg_days'] ?: 0;
    }

    /**
     * الحصول على معدل التحويل الإجمالي
     */
    public function getOverallConversionRate() {
        $query = $this->db->query("
            SELECT
                COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed,
                COUNT(*) as total,
                (COUNT(CASE WHEN status = 'completed' THEN 1 END) / COUNT(*) * 100) as conversion_rate
            FROM " . DB_PREFIX . "crm_customer_journey
        ");

        return round($query->row['conversion_rate'], 2);
    }

    /**
     * الحصول على توزيع صحة الرحلات
     */
    public function getHealthDistribution() {
        $query = $this->db->query("
            SELECT
                CASE
                    WHEN health_score >= 90 THEN 'Excellent'
                    WHEN health_score >= 70 THEN 'Good'
                    WHEN health_score >= 50 THEN 'Fair'
                    ELSE 'Poor'
                END as health_category,
                COUNT(*) as journey_count,
                (COUNT(*) * 100.0 / (SELECT COUNT(*) FROM " . DB_PREFIX . "crm_customer_journey)) as percentage
            FROM " . DB_PREFIX . "crm_customer_journey
            GROUP BY health_category
            ORDER BY MIN(health_score) DESC
        ");

        return $query->rows;
    }

    /**
     * الحصول على بيانات قمع المراحل
     */
    public function getStageFunnelData() {
        $query = $this->db->query("
            SELECT
                current_stage,
                COUNT(*) as journey_count,
                AVG(conversion_probability) as avg_probability,
                SUM(estimated_value) as total_value
            FROM " . DB_PREFIX . "crm_customer_journey
            WHERE status = 'active'
            GROUP BY current_stage
            ORDER BY
                CASE current_stage
                    WHEN 'awareness' THEN 1
                    WHEN 'interest' THEN 2
                    WHEN 'consideration' THEN 3
                    WHEN 'purchase' THEN 4
                    WHEN 'retention' THEN 5
                    WHEN 'advocacy' THEN 6
                    ELSE 7
                END
        ");

        return $query->rows;
    }

    /**
     * الحصول على بيانات خريطة حرارية لنقاط اللمس
     */
    public function getTouchpointHeatmapData() {
        $query = $this->db->query("
            SELECT
                tp.touchpoint_type,
                tp.activity_type,
                COUNT(*) as frequency,
                AVG(tp.engagement_value) as avg_engagement,
                COUNT(DISTINCT tp.journey_id) as unique_journeys
            FROM " . DB_PREFIX . "crm_touchpoint tp
            JOIN " . DB_PREFIX . "crm_customer_journey cj ON (tp.journey_id = cj.journey_id)
            WHERE cj.status = 'active'
            GROUP BY tp.touchpoint_type, tp.activity_type
            ORDER BY frequency DESC
        ");

        return $query->rows;
    }

    /**
     * الحصول على بيانات تدفق التحويل
     */
    public function getConversionFlowData() {
        $query = $this->db->query("
            SELECT
                jsh.old_stage,
                jsh.new_stage,
                COUNT(*) as transition_count,
                AVG(DATEDIFF(jsh.date_created, cj.date_created)) as avg_days_to_transition
            FROM " . DB_PREFIX . "crm_journey_stage_history jsh
            JOIN " . DB_PREFIX . "crm_customer_journey cj ON (jsh.journey_id = cj.journey_id)
            GROUP BY jsh.old_stage, jsh.new_stage
            ORDER BY transition_count DESC
        ");

        return $query->rows;
    }

    /**
     * الحصول على بيانات صحة الرحلة
     */
    public function getHealthScoreData($days = 30) {
        $query = $this->db->query("
            SELECT
                DATE(date_modified) as score_date,
                AVG(health_score) as avg_health_score,
                COUNT(*) as journey_count
            FROM " . DB_PREFIX . "crm_customer_journey
            WHERE date_modified >= DATE_SUB(NOW(), INTERVAL " . (int)$days . " DAY)
            GROUP BY DATE(date_modified)
            ORDER BY score_date ASC
        ");

        return $query->rows;
    }

    /**
     * الحصول على الرحلات المتوقفة
     */
    public function getStalledJourneys($days = 7) {
        $query = $this->db->query("
            SELECT cj.*, c.firstname, c.lastname, c.email, c.company,
                   u.firstname as assigned_firstname, u.lastname as assigned_lastname,
                   DATEDIFF(NOW(), cj.date_modified) as days_stalled
            FROM " . DB_PREFIX . "crm_customer_journey cj
            LEFT JOIN " . DB_PREFIX . "customer c ON (cj.customer_id = c.customer_id)
            LEFT JOIN " . DB_PREFIX . "user u ON (cj.assigned_to = u.user_id)
            WHERE cj.status = 'active'
            AND cj.date_modified < DATE_SUB(NOW(), INTERVAL " . (int)$days . " DAY)
            ORDER BY days_stalled DESC
        ");

        return $query->rows;
    }

    /**
     * الحصول على الرحلات عالية القيمة
     */
    public function getHighValueJourneys($min_value = 10000) {
        $query = $this->db->query("
            SELECT cj.*, c.firstname, c.lastname, c.email, c.company,
                   u.firstname as assigned_firstname, u.lastname as assigned_lastname
            FROM " . DB_PREFIX . "crm_customer_journey cj
            LEFT JOIN " . DB_PREFIX . "customer c ON (cj.customer_id = c.customer_id)
            LEFT JOIN " . DB_PREFIX . "user u ON (cj.assigned_to = u.user_id)
            WHERE cj.status = 'active'
            AND cj.estimated_value >= " . (float)$min_value . "
            ORDER BY cj.estimated_value DESC
        ");

        return $query->rows;
    }

    /**
     * الحصول على الرحلات عالية الاحتمالية
     */
    public function getHighProbabilityJourneys($min_probability = 80) {
        $query = $this->db->query("
            SELECT cj.*, c.firstname, c.lastname, c.email, c.company,
                   u.firstname as assigned_firstname, u.lastname as assigned_lastname
            FROM " . DB_PREFIX . "crm_customer_journey cj
            LEFT JOIN " . DB_PREFIX . "customer c ON (cj.customer_id = c.customer_id)
            LEFT JOIN " . DB_PREFIX . "user u ON (cj.assigned_to = u.user_id)
            WHERE cj.status = 'active'
            AND cj.conversion_probability >= " . (int)$min_probability . "
            ORDER BY cj.conversion_probability DESC, cj.estimated_value DESC
        ");

        return $query->rows;
    }

    /**
     * الحصول على أداء المراحل
     */
    public function getStagePerformance() {
        $query = $this->db->query("
            SELECT
                current_stage,
                COUNT(*) as total_journeys,
                AVG(health_score) as avg_health_score,
                AVG(conversion_probability) as avg_conversion_probability,
                AVG(estimated_value) as avg_estimated_value,
                AVG(DATEDIFF(NOW(), start_date)) as avg_days_in_stage
            FROM " . DB_PREFIX . "crm_customer_journey
            WHERE status = 'active'
            GROUP BY current_stage
            ORDER BY
                CASE current_stage
                    WHEN 'awareness' THEN 1
                    WHEN 'interest' THEN 2
                    WHEN 'consideration' THEN 3
                    WHEN 'purchase' THEN 4
                    WHEN 'retention' THEN 5
                    WHEN 'advocacy' THEN 6
                    ELSE 7
                END
        ");

        return $query->rows;
    }

    /**
     * الحصول على تحليل نقاط اللمس
     */
    public function getTouchpointAnalysis($journey_id = null) {
        $sql = "
            SELECT
                tp.touchpoint_type,
                tp.activity_type,
                COUNT(*) as frequency,
                AVG(tp.engagement_value) as avg_engagement,
                MAX(tp.date_created) as last_interaction,
                COUNT(DISTINCT tp.journey_id) as unique_journeys
            FROM " . DB_PREFIX . "crm_touchpoint tp
            JOIN " . DB_PREFIX . "crm_customer_journey cj ON (tp.journey_id = cj.journey_id)
            WHERE 1=1
        ";

        if ($journey_id) {
            $sql .= " AND tp.journey_id = '" . (int)$journey_id . "'";
        }

        $sql .= "
            GROUP BY tp.touchpoint_type, tp.activity_type
            ORDER BY frequency DESC, avg_engagement DESC
        ";

        $query = $this->db->query($sql);

        return $query->rows;
    }

    /**
     * الحصول على اتجاه التحويل
     */
    public function getConversionTrend($days = 30) {
        $query = $this->db->query("
            SELECT
                DATE(completion_date) as conversion_date,
                COUNT(*) as conversions,
                AVG(estimated_value) as avg_value,
                SUM(estimated_value) as total_value
            FROM " . DB_PREFIX . "crm_customer_journey
            WHERE status = 'completed'
            AND completion_date >= DATE_SUB(NOW(), INTERVAL " . (int)$days . " DAY)
            GROUP BY DATE(completion_date)
            ORDER BY conversion_date ASC
        ");

        return $query->rows;
    }

    /**
     * البحث في الرحلات
     */
    public function searchJourneys($search_term, $limit = 20) {
        $query = $this->db->query("
            SELECT cj.*, c.firstname, c.lastname, c.email, c.company,
                   u.firstname as assigned_firstname, u.lastname as assigned_lastname
            FROM " . DB_PREFIX . "crm_customer_journey cj
            LEFT JOIN " . DB_PREFIX . "customer c ON (cj.customer_id = c.customer_id)
            LEFT JOIN " . DB_PREFIX . "user u ON (cj.assigned_to = u.user_id)
            WHERE CONCAT(c.firstname, ' ', c.lastname) LIKE '%" . $this->db->escape($search_term) . "%'
            OR c.email LIKE '%" . $this->db->escape($search_term) . "%'
            OR c.company LIKE '%" . $this->db->escape($search_term) . "%'
            OR cj.current_stage LIKE '%" . $this->db->escape($search_term) . "%'
            OR cj.notes LIKE '%" . $this->db->escape($search_term) . "%'
            ORDER BY cj.date_created DESC
            LIMIT " . (int)$limit
        );

        return $query->rows;
    }
}
