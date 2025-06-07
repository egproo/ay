<?php
/**
 * API متقدم لإدارة الحملات التسويقية
 * Advanced Campaign Management API Controller
 *
 * الهدف: توفير واجهة برمجة تطبيقات متقدمة لنظام إدارة الحملات التسويقية
 * الميزات: RESTful API، تتبع ROI، تحليل الأداء، أتمتة الحملات
 *
 * @author ERP Team
 * @version 1.0
 * @since 2024
 */

class ControllerApiCampaignManagement extends Controller {

    private $api_version = '1.0';
    private $rate_limit = 600; // requests per hour
    private $campaign_types = array();
    private $campaign_statuses = array();

    /**
     * تهيئة API Controller
     */
    public function __construct($registry) {
        parent::__construct($registry);

        // تحميل النماذج المطلوبة
        $this->load->model('crm/campaign_management');
        $this->load->model('crm/lead_scoring');
        $this->load->model('api/authentication');
        $this->load->model('api/rate_limit');

        // تحميل مكتبات المساعدة
        $this->load->language('api/campaign_management');
        $this->load->helper('api');

        // تهيئة أنواع وحالات الحملات
        $this->initializeCampaignTypes();
        $this->initializeCampaignStatuses();

        // إعداد headers للAPI
        $this->setupApiHeaders();
    }

    /**
     * الحصول على قائمة الحملات
     * GET /api/campaign-management/campaigns
     */
    public function getCampaigns() {
        try {
            // التحقق من المصادقة
            $auth_result = $this->authenticateRequest();
            if (!$auth_result['success']) {
                return $this->sendErrorResponse(401, 'Unauthorized', $auth_result['error']);
            }

            // معالجة المعاملات
            $filter_data = $this->processApiFilters();

            // الحصول على البيانات
            $campaigns = $this->model_crm_campaign_management->getCampaigns($filter_data);
            $total = $this->model_crm_campaign_management->getTotalCampaigns($filter_data);

            // تنسيق البيانات للAPI
            $formatted_campaigns = $this->formatCampaignsForApi($campaigns);

            // إعداد الاستجابة
            $response = array(
                'success' => true,
                'data' => $formatted_campaigns,
                'meta' => array(
                    'total' => $total,
                    'count' => count($formatted_campaigns),
                    'page' => $filter_data['page'],
                    'limit' => $filter_data['limit'],
                    'pages' => ceil($total / $filter_data['limit'])
                ),
                'api_version' => $this->api_version,
                'timestamp' => date('c')
            );

            return $this->sendSuccessResponse($response);

        } catch (Exception $e) {
            return $this->sendErrorResponse(500, 'Internal Server Error', $e->getMessage());
        }
    }

    /**
     * الحصول على حملة محددة
     * GET /api/campaign-management/campaigns/{id}
     */
    public function getCampaign() {
        try {
            // التحقق من المصادقة
            $auth_result = $this->authenticateRequest();
            if (!$auth_result['success']) {
                return $this->sendErrorResponse(401, 'Unauthorized', $auth_result['error']);
            }

            $campaign_id = isset($this->request->get['id']) ? (int)$this->request->get['id'] : 0;

            if (!$campaign_id) {
                return $this->sendErrorResponse(400, 'Bad Request', 'Campaign ID is required');
            }

            // الحصول على الحملة
            $campaign = $this->model_crm_campaign_management->getCampaign($campaign_id);

            if (!$campaign) {
                return $this->sendErrorResponse(404, 'Not Found', 'Campaign not found');
            }

            // تنسيق البيانات
            $formatted_campaign = $this->formatCampaignForApi($campaign);

            // إضافة تفاصيل إضافية
            $formatted_campaign['performance_metrics'] = $this->getCampaignPerformanceMetrics($campaign_id);
            $formatted_campaign['lead_breakdown'] = $this->getCampaignLeadBreakdown($campaign_id);
            $formatted_campaign['roi_analysis'] = $this->getCampaignROIAnalysis($campaign);

            $response = array(
                'success' => true,
                'data' => $formatted_campaign,
                'api_version' => $this->api_version,
                'timestamp' => date('c')
            );

            return $this->sendSuccessResponse($response);

        } catch (Exception $e) {
            return $this->sendErrorResponse(500, 'Internal Server Error', $e->getMessage());
        }
    }

    /**
     * إنشاء حملة جديدة
     * POST /api/campaign-management/campaigns
     */
    public function createCampaign() {
        try {
            // التحقق من المصادقة والصلاحيات
            $auth_result = $this->authenticateRequest();
            if (!$auth_result['success']) {
                return $this->sendErrorResponse(401, 'Unauthorized', $auth_result['error']);
            }

            if (!$this->hasPermission($auth_result['user_id'], 'campaign_create')) {
                return $this->sendErrorResponse(403, 'Forbidden', 'Insufficient permissions');
            }

            // الحصول على البيانات
            $input_data = $this->getJsonInput();

            // التحقق من صحة البيانات
            $validation_result = $this->validateCampaignData($input_data);
            if (!$validation_result['valid']) {
                return $this->sendErrorResponse(422, 'Validation Error', $validation_result['errors']);
            }

            // إعداد بيانات الحملة
            $campaign_data = $this->prepareCampaignData($input_data, $auth_result['user_id']);

            // إنشاء الحملة
            $campaign_id = $this->model_crm_campaign_management->addCampaign($campaign_data);

            // الحصول على الحملة المحفوظة
            $saved_campaign = $this->model_crm_campaign_management->getCampaign($campaign_id);
            $formatted_campaign = $this->formatCampaignForApi($saved_campaign);

            $response = array(
                'success' => true,
                'message' => 'Campaign created successfully',
                'data' => $formatted_campaign,
                'api_version' => $this->api_version,
                'timestamp' => date('c')
            );

            return $this->sendSuccessResponse($response, 201);

        } catch (Exception $e) {
            return $this->sendErrorResponse(500, 'Internal Server Error', $e->getMessage());
        }
    }

    /**
     * تحديث حالة الحملة
     * PUT /api/campaign-management/campaigns/{id}/status
     */
    public function updateStatus() {
        try {
            // التحقق من المصادقة والصلاحيات
            $auth_result = $this->authenticateRequest();
            if (!$auth_result['success']) {
                return $this->sendErrorResponse(401, 'Unauthorized', $auth_result['error']);
            }

            if (!$this->hasPermission($auth_result['user_id'], 'campaign_update')) {
                return $this->sendErrorResponse(403, 'Forbidden', 'Insufficient permissions');
            }

            $campaign_id = isset($this->request->get['id']) ? (int)$this->request->get['id'] : 0;

            if (!$campaign_id) {
                return $this->sendErrorResponse(400, 'Bad Request', 'Campaign ID is required');
            }

            // الحصول على البيانات
            $input_data = $this->getJsonInput();

            if (empty($input_data['status'])) {
                return $this->sendErrorResponse(400, 'Bad Request', 'Status is required');
            }

            if (!isset($this->campaign_statuses[$input_data['status']])) {
                return $this->sendErrorResponse(400, 'Bad Request', 'Invalid status specified');
            }

            // التحقق من وجود الحملة
            $campaign = $this->model_crm_campaign_management->getCampaign($campaign_id);
            if (!$campaign) {
                return $this->sendErrorResponse(404, 'Not Found', 'Campaign not found');
            }

            $old_status = $campaign['status'];
            $new_status = $input_data['status'];

            // التحقق من صحة التغيير
            $status_change_validation = $this->validateStatusChange($old_status, $new_status);
            if (!$status_change_validation['valid']) {
                return $this->sendErrorResponse(400, 'Invalid Status Change', $status_change_validation['error']);
            }

            // تحديث الحالة
            $this->model_crm_campaign_management->updateCampaignStatus($campaign_id, $new_status);

            // تطبيق إجراءات إضافية حسب الحالة الجديدة
            $this->applyStatusChangeActions($campaign_id, $old_status, $new_status);

            // الحصول على الحملة المحدثة
            $updated_campaign = $this->model_crm_campaign_management->getCampaign($campaign_id);
            $formatted_campaign = $this->formatCampaignForApi($updated_campaign);

            $response = array(
                'success' => true,
                'message' => 'Campaign status updated successfully',
                'data' => $formatted_campaign,
                'status_change' => array(
                    'old_status' => $old_status,
                    'new_status' => $new_status,
                    'changed_at' => date('c')
                ),
                'api_version' => $this->api_version,
                'timestamp' => date('c')
            );

            return $this->sendSuccessResponse($response);

        } catch (Exception $e) {
            return $this->sendErrorResponse(500, 'Internal Server Error', $e->getMessage());
        }
    }

    /**
     * تحديث الميزانية المنفقة
     * PUT /api/campaign-management/campaigns/{id}/spending
     */
    public function updateSpending() {
        try {
            // التحقق من المصادقة والصلاحيات
            $auth_result = $this->authenticateRequest();
            if (!$auth_result['success']) {
                return $this->sendErrorResponse(401, 'Unauthorized', $auth_result['error']);
            }

            if (!$this->hasPermission($auth_result['user_id'], 'campaign_update')) {
                return $this->sendErrorResponse(403, 'Forbidden', 'Insufficient permissions');
            }

            $campaign_id = isset($this->request->get['id']) ? (int)$this->request->get['id'] : 0;

            if (!$campaign_id) {
                return $this->sendErrorResponse(400, 'Bad Request', 'Campaign ID is required');
            }

            // الحصول على البيانات
            $input_data = $this->getJsonInput();

            if (!isset($input_data['amount']) || !is_numeric($input_data['amount'])) {
                return $this->sendErrorResponse(400, 'Bad Request', 'Valid amount is required');
            }

            $amount = (float)$input_data['amount'];

            if ($amount <= 0) {
                return $this->sendErrorResponse(400, 'Bad Request', 'Amount must be positive');
            }

            // التحقق من وجود الحملة
            $campaign = $this->model_crm_campaign_management->getCampaign($campaign_id);
            if (!$campaign) {
                return $this->sendErrorResponse(404, 'Not Found', 'Campaign not found');
            }

            // التحقق من تجاوز الميزانية
            $new_total_spent = $campaign['spent_amount'] + $amount;
            $budget_exceeded = $new_total_spent > $campaign['budget'];

            // تحديث المبلغ المنفق
            $this->model_crm_campaign_management->updateSpentAmount($campaign_id, $amount);

            // إضافة مقاييس الإنفاق
            $this->model_crm_campaign_management->addCampaignMetrics($campaign_id, array(
                'metric_type' => 'spending',
                'metric_value' => $amount,
                'metric_date' => date('Y-m-d H:i:s'),
                'notes' => isset($input_data['notes']) ? $input_data['notes'] : 'API spending update'
            ));

            // إرسال تنبيه إذا تم تجاوز الميزانية
            if ($budget_exceeded) {
                $this->sendBudgetExceededAlert($campaign_id, $new_total_spent, $campaign['budget']);
            }

            // الحصول على الحملة المحدثة
            $updated_campaign = $this->model_crm_campaign_management->getCampaign($campaign_id);
            $formatted_campaign = $this->formatCampaignForApi($updated_campaign);

            $response = array(
                'success' => true,
                'message' => 'Campaign spending updated successfully',
                'data' => $formatted_campaign,
                'spending_update' => array(
                    'amount_added' => $amount,
                    'previous_total' => $campaign['spent_amount'],
                    'new_total' => $new_total_spent,
                    'budget_exceeded' => $budget_exceeded,
                    'remaining_budget' => max(0, $campaign['budget'] - $new_total_spent)
                ),
                'api_version' => $this->api_version,
                'timestamp' => date('c')
            );

            return $this->sendSuccessResponse($response);

        } catch (Exception $e) {
            return $this->sendErrorResponse(500, 'Internal Server Error', $e->getMessage());
        }
    }

    /**
     * تحليل أداء الحملة
     * GET /api/campaign-management/campaigns/{id}/analytics
     */
    public function analyzeCampaign() {
        try {
            // التحقق من المصادقة
            $auth_result = $this->authenticateRequest();
            if (!$auth_result['success']) {
                return $this->sendErrorResponse(401, 'Unauthorized', $auth_result['error']);
            }

            $campaign_id = isset($this->request->get['id']) ? (int)$this->request->get['id'] : 0;

            if (!$campaign_id) {
                return $this->sendErrorResponse(400, 'Bad Request', 'Campaign ID is required');
            }

            // التحقق من وجود الحملة
            $campaign = $this->model_crm_campaign_management->getCampaign($campaign_id);
            if (!$campaign) {
                return $this->sendErrorResponse(404, 'Not Found', 'Campaign not found');
            }

            // تحليل شامل للحملة
            $analytics = $this->performCampaignAnalysis($campaign_id);

            $response = array(
                'success' => true,
                'data' => $analytics,
                'api_version' => $this->api_version,
                'timestamp' => date('c')
            );

            return $this->sendSuccessResponse($response);

        } catch (Exception $e) {
            return $this->sendErrorResponse(500, 'Internal Server Error', $e->getMessage());
        }
    }

    /**
     * الحصول على إحصائيات الحملات
     * GET /api/campaign-management/statistics
     */
    public function getStatistics() {
        try {
            // التحقق من المصادقة
            $auth_result = $this->authenticateRequest();
            if (!$auth_result['success']) {
                return $this->sendErrorResponse(401, 'Unauthorized', $auth_result['error']);
            }

            // الحصول على الإحصائيات
            $statistics = $this->model_crm_campaign_management->getCampaignStatistics();
            $top_performing = $this->model_crm_campaign_management->getTopPerformingCampaigns(10);
            $budget_utilization = $this->model_crm_campaign_management->getBudgetUtilization();
            $conversion_rates = $this->model_crm_campaign_management->getConversionRates();
            $channel_performance = $this->model_crm_campaign_management->getChannelPerformance();
            $performance_trend = $this->model_crm_campaign_management->getPerformanceTrend(30);

            $response = array(
                'success' => true,
                'data' => array(
                    'overview' => $statistics,
                    'top_performing_campaigns' => $top_performing,
                    'budget_utilization' => $budget_utilization,
                    'conversion_rates' => $conversion_rates,
                    'channel_performance' => $channel_performance,
                    'performance_trend' => $performance_trend,
                    'available_types' => $this->campaign_types,
                    'available_statuses' => $this->campaign_statuses
                ),
                'api_version' => $this->api_version,
                'timestamp' => date('c')
            );

            return $this->sendSuccessResponse($response);

        } catch (Exception $e) {
            return $this->sendErrorResponse(500, 'Internal Server Error', $e->getMessage());
        }
    }

    /**
     * تهيئة أنواع الحملات
     */
    private function initializeCampaignTypes() {
        $this->campaign_types = array(
            'email' => array(
                'name' => 'البريد الإلكتروني',
                'description' => 'حملات البريد الإلكتروني المباشر',
                'cost_per_lead' => 2.5,
                'avg_conversion_rate' => 3.2
            ),
            'social_media' => array(
                'name' => 'وسائل التواصل الاجتماعي',
                'description' => 'حملات على منصات التواصل الاجتماعي',
                'cost_per_lead' => 4.8,
                'avg_conversion_rate' => 2.1
            ),
            'paid_search' => array(
                'name' => 'البحث المدفوع',
                'description' => 'إعلانات محركات البحث',
                'cost_per_lead' => 8.2,
                'avg_conversion_rate' => 4.7
            ),
            'display' => array(
                'name' => 'الإعلانات المرئية',
                'description' => 'إعلانات البانر والعرض',
                'cost_per_lead' => 3.1,
                'avg_conversion_rate' => 1.8
            ),
            'content' => array(
                'name' => 'تسويق المحتوى',
                'description' => 'حملات المحتوى والمدونات',
                'cost_per_lead' => 1.9,
                'avg_conversion_rate' => 5.3
            ),
            'event' => array(
                'name' => 'الفعاليات',
                'description' => 'المعارض والندوات والفعاليات',
                'cost_per_lead' => 15.6,
                'avg_conversion_rate' => 8.9
            ),
            'referral' => array(
                'name' => 'الإحالات',
                'description' => 'برامج الإحالة والتوصيات',
                'cost_per_lead' => 5.4,
                'avg_conversion_rate' => 12.3
            ),
            'retargeting' => array(
                'name' => 'إعادة الاستهداف',
                'description' => 'استهداف الزوار السابقين',
                'cost_per_lead' => 6.7,
                'avg_conversion_rate' => 7.2
            )
        );
    }

    /**
     * تهيئة حالات الحملات
     */
    private function initializeCampaignStatuses() {
        $this->campaign_statuses = array(
            'draft' => array(
                'name' => 'مسودة',
                'description' => 'الحملة قيد الإعداد',
                'color' => '#6c757d',
                'allowed_transitions' => array('active', 'cancelled')
            ),
            'active' => array(
                'name' => 'نشطة',
                'description' => 'الحملة قيد التشغيل',
                'color' => '#28a745',
                'allowed_transitions' => array('paused', 'completed', 'cancelled')
            ),
            'paused' => array(
                'name' => 'متوقفة مؤقتاً',
                'description' => 'الحملة متوقفة مؤقتاً',
                'color' => '#ffc107',
                'allowed_transitions' => array('active', 'cancelled')
            ),
            'completed' => array(
                'name' => 'مكتملة',
                'description' => 'الحملة انتهت بنجاح',
                'color' => '#007bff',
                'allowed_transitions' => array()
            ),
            'cancelled' => array(
                'name' => 'ملغية',
                'description' => 'الحملة تم إلغاؤها',
                'color' => '#dc3545',
                'allowed_transitions' => array()
            )
        );
    }

    /**
     * إعداد headers للAPI
     */
    private function setupApiHeaders() {
        header('Content-Type: application/json; charset=utf-8');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-API-Key');
        header('X-API-Version: ' . $this->api_version);
        header('X-Rate-Limit: ' . $this->rate_limit);

        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit();
        }
    }

    /**
     * التحقق من المصادقة
     */
    private function authenticateRequest() {
        $api_key = $this->getApiKey();

        if (empty($api_key)) {
            return array('success' => false, 'error' => 'API key is required');
        }

        $auth_result = $this->model_api_authentication->validateApiKey($api_key);

        if (!$auth_result['valid']) {
            return array('success' => false, 'error' => 'Invalid API key');
        }

        if (!$auth_result['active']) {
            return array('success' => false, 'error' => 'API key is inactive');
        }

        return array(
            'success' => true,
            'api_key' => $api_key,
            'user_id' => $auth_result['user_id'],
            'permissions' => $auth_result['permissions']
        );
    }

    /**
     * الحصول على API Key
     */
    private function getApiKey() {
        if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            $auth_header = $_SERVER['HTTP_AUTHORIZATION'];
            if (preg_match('/Bearer\s+(.*)$/i', $auth_header, $matches)) {
                return $matches[1];
            }
        }

        if (isset($_SERVER['HTTP_X_API_KEY'])) {
            return $_SERVER['HTTP_X_API_KEY'];
        }

        if (isset($this->request->get['api_key'])) {
            return $this->request->get['api_key'];
        }

        return null;
    }

    /**
     * التحقق من الصلاحيات
     */
    private function hasPermission($user_id, $permission) {
        return $this->model_api_authentication->hasPermission($user_id, $permission);
    }

    /**
     * معالجة فلاتر API
     */
    private function processApiFilters() {
        $filter_data = array();

        $filter_data['filter_name'] = isset($this->request->get['name']) ? $this->request->get['name'] : '';
        $filter_data['filter_type'] = isset($this->request->get['type']) ? $this->request->get['type'] : '';
        $filter_data['filter_status'] = isset($this->request->get['status']) ? $this->request->get['status'] : '';
        $filter_data['filter_performance'] = isset($this->request->get['performance']) ? $this->request->get['performance'] : '';
        $filter_data['filter_assigned_to'] = isset($this->request->get['assigned_to']) ? $this->request->get['assigned_to'] : '';

        $filter_data['filter_date_from'] = isset($this->request->get['date_from']) ? $this->request->get['date_from'] : '';
        $filter_data['filter_date_to'] = isset($this->request->get['date_to']) ? $this->request->get['date_to'] : '';

        $filter_data['sort'] = isset($this->request->get['sort']) ? $this->request->get['sort'] : 'date_created';
        $filter_data['order'] = isset($this->request->get['order']) ? strtoupper($this->request->get['order']) : 'DESC';

        $filter_data['page'] = isset($this->request->get['page']) ? max(1, (int)$this->request->get['page']) : 1;
        $filter_data['limit'] = isset($this->request->get['limit']) ? min(100, max(1, (int)$this->request->get['limit'])) : 20;
        $filter_data['start'] = ($filter_data['page'] - 1) * $filter_data['limit'];

        return $filter_data;
    }

    /**
     * تنسيق الحملات للAPI
     */
    private function formatCampaignsForApi($campaigns) {
        $formatted = array();

        foreach ($campaigns as $campaign) {
            $formatted[] = $this->formatCampaignForApi($campaign);
        }

        return $formatted;
    }

    /**
     * تنسيق حملة واحدة للAPI
     */
    private function formatCampaignForApi($campaign) {
        return array(
            'id' => (int)$campaign['campaign_id'],
            'name' => $campaign['name'],
            'description' => $campaign['description'],
            'campaign_type' => $campaign['campaign_type'],
            'type_info' => $this->campaign_types[$campaign['campaign_type']] ?? null,
            'budget' => (float)$campaign['budget'],
            'spent_amount' => (float)$campaign['spent_amount'],
            'remaining_budget' => (float)$campaign['remaining_budget'],
            'budget_utilization' => round($campaign['budget_utilization'], 2),
            'status' => $campaign['status'],
            'status_info' => $this->campaign_statuses[$campaign['status']] ?? null,
            'dates' => array(
                'start' => $campaign['start_date'],
                'end' => $campaign['end_date'],
                'created' => $campaign['date_created'],
                'modified' => $campaign['date_modified']
            ),
            'target_audience' => $campaign['target_audience'] ? json_decode($campaign['target_audience'], true) : null,
            'channels' => $campaign['channels'] ? json_decode($campaign['channels'], true) : null,
            'goals' => $campaign['goals'] ? json_decode($campaign['goals'], true) : null,
            'performance' => array(
                'lead_count' => (int)$campaign['lead_count'],
                'converted_count' => (int)$campaign['converted_count'],
                'conversion_rate' => round($campaign['conversion_rate'], 2),
                'roi_percentage' => round($campaign['roi_percentage'], 2),
                'cost_per_lead' => $campaign['lead_count'] > 0 ? round($campaign['spent_amount'] / $campaign['lead_count'], 2) : 0,
                'cost_per_conversion' => $campaign['converted_count'] > 0 ? round($campaign['spent_amount'] / $campaign['converted_count'], 2) : 0
            ),
            'created_by' => array(
                'id' => (int)$campaign['created_by'],
                'name' => trim($campaign['firstname'] . ' ' . $campaign['lastname'])
            )
        );
    }

    /**
     * الحصول على بيانات JSON
     */
    private function getJsonInput() {
        $input = file_get_contents('php://input');
        return json_decode($input, true);
    }

    /**
     * التحقق من صحة بيانات الحملة
     */
    private function validateCampaignData($data) {
        $errors = array();

        if (empty($data['name'])) {
            $errors[] = 'Campaign name is required';
        }

        if (empty($data['campaign_type'])) {
            $errors[] = 'Campaign type is required';
        } elseif (!isset($this->campaign_types[$data['campaign_type']])) {
            $errors[] = 'Invalid campaign type specified';
        }

        if (empty($data['budget']) || !is_numeric($data['budget']) || $data['budget'] <= 0) {
            $errors[] = 'Valid budget amount is required';
        }

        if (empty($data['start_date'])) {
            $errors[] = 'Start date is required';
        }

        if (empty($data['end_date'])) {
            $errors[] = 'End date is required';
        }

        if (!empty($data['start_date']) && !empty($data['end_date'])) {
            if (strtotime($data['end_date']) <= strtotime($data['start_date'])) {
                $errors[] = 'End date must be after start date';
            }
        }

        return array(
            'valid' => empty($errors),
            'errors' => $errors
        );
    }

    /**
     * التحقق من صحة تغيير الحالة
     */
    private function validateStatusChange($old_status, $new_status) {
        if (!isset($this->campaign_statuses[$old_status])) {
            return array('valid' => false, 'error' => 'Invalid current status');
        }

        if (!isset($this->campaign_statuses[$new_status])) {
            return array('valid' => false, 'error' => 'Invalid new status');
        }

        $allowed_transitions = $this->campaign_statuses[$old_status]['allowed_transitions'];

        if (!in_array($new_status, $allowed_transitions)) {
            return array('valid' => false, 'error' => 'Status transition not allowed');
        }

        return array('valid' => true);
    }

    /**
     * إعداد بيانات الحملة
     */
    private function prepareCampaignData($input_data, $user_id) {
        return array(
            'name' => $input_data['name'],
            'description' => isset($input_data['description']) ? $input_data['description'] : '',
            'campaign_type' => $input_data['campaign_type'],
            'budget' => (float)$input_data['budget'],
            'start_date' => $input_data['start_date'],
            'end_date' => $input_data['end_date'],
            'target_audience' => isset($input_data['target_audience']) ? json_encode($input_data['target_audience']) : '{}',
            'channels' => isset($input_data['channels']) ? json_encode($input_data['channels']) : '{}',
            'goals' => isset($input_data['goals']) ? json_encode($input_data['goals']) : '{}',
            'status' => isset($input_data['status']) ? $input_data['status'] : 'draft',
            'created_by' => $user_id
        );
    }

    /**
     * إرسال استجابة نجاح
     */
    private function sendSuccessResponse($data, $status_code = 200) {
        http_response_code($status_code);
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit();
    }

    /**
     * إرسال استجابة خطأ
     */
    private function sendErrorResponse($status_code, $error_type, $message) {
        http_response_code($status_code);

        $response = array(
            'success' => false,
            'error' => array(
                'type' => $error_type,
                'message' => $message,
                'code' => $status_code
            ),
            'api_version' => $this->api_version,
            'timestamp' => date('c')
        );

        echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit();
    }
}
