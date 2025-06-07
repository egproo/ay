<?php
/**
 * API متقدم لرحلة العميل
 * Advanced Customer Journey API Controller
 *
 * الهدف: توفير واجهة برمجة تطبيقات متقدمة لنظام رحلة العميل
 * الميزات: RESTful API، تتبع متقدم، تحليل نقاط اللمس، تحسين الرحلة
 *
 * @author ERP Team
 * @version 1.0
 * @since 2024
 */

class ControllerApiCustomerJourney extends Controller {

    private $api_version = '1.0';
    private $rate_limit = 800; // requests per hour
    private $journey_stages = array();
    private $touchpoint_types = array();

    /**
     * تهيئة API Controller
     */
    public function __construct($registry) {
        parent::__construct($registry);

        // تحميل النماذج المطلوبة
        $this->load->model('crm/customer_journey');
        $this->load->model('crm/touchpoint');
        $this->load->model('api/authentication');
        $this->load->model('api/rate_limit');

        // تحميل مكتبات المساعدة
        $this->load->language('api/customer_journey');
        $this->load->helper('api');

        // تهيئة مراحل الرحلة وأنواع نقاط اللمس
        $this->initializeJourneyStages();
        $this->initializeTouchpointTypes();

        // إعداد headers للAPI
        $this->setupApiHeaders();
    }

    /**
     * الحصول على قائمة رحلات العملاء
     * GET /api/customer-journey/journeys
     */
    public function getJourneys() {
        try {
            // التحقق من المصادقة
            $auth_result = $this->authenticateRequest();
            if (!$auth_result['success']) {
                return $this->sendErrorResponse(401, 'Unauthorized', $auth_result['error']);
            }

            // معالجة المعاملات
            $filter_data = $this->processApiFilters();

            // الحصول على البيانات
            $journeys = $this->model_crm_customer_journey->getJourneys($filter_data);
            $total = $this->model_crm_customer_journey->getTotalJourneys($filter_data);

            // تنسيق البيانات للAPI
            $formatted_journeys = $this->formatJourneysForApi($journeys);

            // إعداد الاستجابة
            $response = array(
                'success' => true,
                'data' => $formatted_journeys,
                'meta' => array(
                    'total' => $total,
                    'count' => count($formatted_journeys),
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
     * الحصول على رحلة عميل محددة
     * GET /api/customer-journey/journeys/{id}
     */
    public function getJourney() {
        try {
            // التحقق من المصادقة
            $auth_result = $this->authenticateRequest();
            if (!$auth_result['success']) {
                return $this->sendErrorResponse(401, 'Unauthorized', $auth_result['error']);
            }

            $journey_id = isset($this->request->get['id']) ? (int)$this->request->get['id'] : 0;

            if (!$journey_id) {
                return $this->sendErrorResponse(400, 'Bad Request', 'Journey ID is required');
            }

            // الحصول على الرحلة
            $journey = $this->model_crm_customer_journey->getJourney($journey_id);

            if (!$journey) {
                return $this->sendErrorResponse(404, 'Not Found', 'Journey not found');
            }

            // تنسيق البيانات
            $formatted_journey = $this->formatJourneyForApi($journey);

            // إضافة تفاصيل إضافية
            $formatted_journey['touchpoints'] = $this->getJourneyTouchpoints($journey_id);
            $formatted_journey['stage_history'] = $this->getJourneyStageHistory($journey_id);
            $formatted_journey['analytics'] = $this->getJourneyAnalytics($journey_id);

            $response = array(
                'success' => true,
                'data' => $formatted_journey,
                'api_version' => $this->api_version,
                'timestamp' => date('c')
            );

            return $this->sendSuccessResponse($response);

        } catch (Exception $e) {
            return $this->sendErrorResponse(500, 'Internal Server Error', $e->getMessage());
        }
    }

    /**
     * إنشاء رحلة عميل جديدة
     * POST /api/customer-journey/journeys
     */
    public function createJourney() {
        try {
            // التحقق من المصادقة والصلاحيات
            $auth_result = $this->authenticateRequest();
            if (!$auth_result['success']) {
                return $this->sendErrorResponse(401, 'Unauthorized', $auth_result['error']);
            }

            if (!$this->hasPermission($auth_result['user_id'], 'journey_create')) {
                return $this->sendErrorResponse(403, 'Forbidden', 'Insufficient permissions');
            }

            // الحصول على البيانات
            $input_data = $this->getJsonInput();

            // التحقق من صحة البيانات
            $validation_result = $this->validateJourneyData($input_data);
            if (!$validation_result['valid']) {
                return $this->sendErrorResponse(422, 'Validation Error', $validation_result['errors']);
            }

            // إعداد بيانات الرحلة
            $journey_data = $this->prepareJourneyData($input_data, $auth_result['user_id']);

            // إنشاء الرحلة
            $journey_id = $this->model_crm_customer_journey->addJourney($journey_data);

            // الحصول على الرحلة المحفوظة
            $saved_journey = $this->model_crm_customer_journey->getJourney($journey_id);
            $formatted_journey = $this->formatJourneyForApi($saved_journey);

            $response = array(
                'success' => true,
                'message' => 'Journey created successfully',
                'data' => $formatted_journey,
                'api_version' => $this->api_version,
                'timestamp' => date('c')
            );

            return $this->sendSuccessResponse($response, 201);

        } catch (Exception $e) {
            return $this->sendErrorResponse(500, 'Internal Server Error', $e->getMessage());
        }
    }

    /**
     * تحديث مرحلة الرحلة
     * PUT /api/customer-journey/journeys/{id}/stage
     */
    public function updateStage() {
        try {
            // التحقق من المصادقة والصلاحيات
            $auth_result = $this->authenticateRequest();
            if (!$auth_result['success']) {
                return $this->sendErrorResponse(401, 'Unauthorized', $auth_result['error']);
            }

            if (!$this->hasPermission($auth_result['user_id'], 'journey_update')) {
                return $this->sendErrorResponse(403, 'Forbidden', 'Insufficient permissions');
            }

            $journey_id = isset($this->request->get['id']) ? (int)$this->request->get['id'] : 0;

            if (!$journey_id) {
                return $this->sendErrorResponse(400, 'Bad Request', 'Journey ID is required');
            }

            // الحصول على البيانات
            $input_data = $this->getJsonInput();

            if (empty($input_data['new_stage'])) {
                return $this->sendErrorResponse(400, 'Bad Request', 'New stage is required');
            }

            if (!isset($this->journey_stages[$input_data['new_stage']])) {
                return $this->sendErrorResponse(400, 'Bad Request', 'Invalid stage specified');
            }

            // التحقق من وجود الرحلة
            $journey = $this->model_crm_customer_journey->getJourney($journey_id);
            if (!$journey) {
                return $this->sendErrorResponse(404, 'Not Found', 'Journey not found');
            }

            $old_stage = $journey['current_stage'];
            $new_stage = $input_data['new_stage'];
            $notes = isset($input_data['notes']) ? $input_data['notes'] : '';

            // تحديث المرحلة
            $this->model_crm_customer_journey->updateJourneyStage($journey_id, $new_stage, $notes);

            // حساب احتمالية التحويل الجديدة
            $conversion_probability = $this->calculateConversionProbability($journey_id, $new_stage);
            $this->model_crm_customer_journey->updateConversionProbability($journey_id, $conversion_probability);

            // الحصول على الرحلة المحدثة
            $updated_journey = $this->model_crm_customer_journey->getJourney($journey_id);
            $formatted_journey = $this->formatJourneyForApi($updated_journey);

            $response = array(
                'success' => true,
                'message' => 'Stage updated successfully',
                'data' => $formatted_journey,
                'stage_change' => array(
                    'old_stage' => $old_stage,
                    'new_stage' => $new_stage,
                    'conversion_probability_change' => $conversion_probability - $journey['conversion_probability'],
                    'notes' => $notes
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
     * إضافة نقطة لمس جديدة
     * POST /api/customer-journey/journeys/{id}/touchpoints
     */
    public function addTouchpoint() {
        try {
            // التحقق من المصادقة والصلاحيات
            $auth_result = $this->authenticateRequest();
            if (!$auth_result['success']) {
                return $this->sendErrorResponse(401, 'Unauthorized', $auth_result['error']);
            }

            if (!$this->hasPermission($auth_result['user_id'], 'touchpoint_create')) {
                return $this->sendErrorResponse(403, 'Forbidden', 'Insufficient permissions');
            }

            $journey_id = isset($this->request->get['id']) ? (int)$this->request->get['id'] : 0;

            if (!$journey_id) {
                return $this->sendErrorResponse(400, 'Bad Request', 'Journey ID is required');
            }

            // التحقق من وجود الرحلة
            $journey = $this->model_crm_customer_journey->getJourney($journey_id);
            if (!$journey) {
                return $this->sendErrorResponse(404, 'Not Found', 'Journey not found');
            }

            // الحصول على البيانات
            $input_data = $this->getJsonInput();

            // التحقق من صحة البيانات
            $validation_result = $this->validateTouchpointData($input_data);
            if (!$validation_result['valid']) {
                return $this->sendErrorResponse(422, 'Validation Error', $validation_result['errors']);
            }

            // إضافة نقطة اللمس
            $touchpoint_data = array(
                'journey_id' => $journey_id,
                'touchpoint_type' => $input_data['touchpoint_type'],
                'activity_type' => $input_data['activity_type'],
                'description' => $input_data['description'],
                'engagement_value' => isset($input_data['engagement_value']) ? (int)$input_data['engagement_value'] : $this->touchpoint_types[$input_data['touchpoint_type']]['engagement_value'],
                'user_id' => $auth_result['user_id'],
                'date_created' => date('Y-m-d H:i:s')
            );

            $touchpoint_id = $this->model_crm_touchpoint->addTouchpoint($touchpoint_data);

            // تحديث صحة الرحلة
            $this->updateJourneyHealth($journey_id);

            $response = array(
                'success' => true,
                'message' => 'Touchpoint added successfully',
                'data' => array(
                    'touchpoint_id' => $touchpoint_id,
                    'journey_id' => $journey_id,
                    'touchpoint_data' => $touchpoint_data
                ),
                'api_version' => $this->api_version,
                'timestamp' => date('c')
            );

            return $this->sendSuccessResponse($response, 201);

        } catch (Exception $e) {
            return $this->sendErrorResponse(500, 'Internal Server Error', $e->getMessage());
        }
    }

    /**
     * تحليل الرحلة
     * GET /api/customer-journey/journeys/{id}/analytics
     */
    public function analyzeJourney() {
        try {
            // التحقق من المصادقة
            $auth_result = $this->authenticateRequest();
            if (!$auth_result['success']) {
                return $this->sendErrorResponse(401, 'Unauthorized', $auth_result['error']);
            }

            $journey_id = isset($this->request->get['id']) ? (int)$this->request->get['id'] : 0;

            if (!$journey_id) {
                return $this->sendErrorResponse(400, 'Bad Request', 'Journey ID is required');
            }

            // التحقق من وجود الرحلة
            $journey = $this->model_crm_customer_journey->getJourney($journey_id);
            if (!$journey) {
                return $this->sendErrorResponse(404, 'Not Found', 'Journey not found');
            }

            // تحليل شامل للرحلة
            $analytics = $this->performJourneyAnalysis($journey_id);

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
     * الحصول على إحصائيات الرحلات
     * GET /api/customer-journey/statistics
     */
    public function getStatistics() {
        try {
            // التحقق من المصادقة
            $auth_result = $this->authenticateRequest();
            if (!$auth_result['success']) {
                return $this->sendErrorResponse(401, 'Unauthorized', $auth_result['error']);
            }

            // الحصول على الإحصائيات
            $statistics = $this->model_crm_customer_journey->getJourneyStatistics();
            $stage_funnel = $this->model_crm_customer_journey->getStageFunnelData();
            $touchpoint_heatmap = $this->model_crm_customer_journey->getTouchpointHeatmapData();
            $conversion_flow = $this->model_crm_customer_journey->getConversionFlowData();
            $health_distribution = $this->model_crm_customer_journey->getHealthDistribution();

            $response = array(
                'success' => true,
                'data' => array(
                    'overview' => $statistics,
                    'stage_funnel' => $stage_funnel,
                    'touchpoint_heatmap' => $touchpoint_heatmap,
                    'conversion_flow' => $conversion_flow,
                    'health_distribution' => $health_distribution,
                    'available_stages' => $this->journey_stages,
                    'available_touchpoints' => $this->touchpoint_types
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
     * تهيئة مراحل الرحلة
     */
    private function initializeJourneyStages() {
        $this->journey_stages = array(
            'awareness' => array(
                'name' => 'الوعي',
                'description' => 'العميل يدرك وجود المنتج أو الخدمة',
                'order' => 1,
                'conversion_weight' => 0.1
            ),
            'interest' => array(
                'name' => 'الاهتمام',
                'description' => 'العميل يظهر اهتماماً بالمنتج أو الخدمة',
                'order' => 2,
                'conversion_weight' => 0.2
            ),
            'consideration' => array(
                'name' => 'الاعتبار',
                'description' => 'العميل يفكر جدياً في الشراء',
                'order' => 3,
                'conversion_weight' => 0.4
            ),
            'purchase' => array(
                'name' => 'الشراء',
                'description' => 'العميل يقوم بعملية الشراء',
                'order' => 4,
                'conversion_weight' => 1.0
            ),
            'retention' => array(
                'name' => 'الاحتفاظ',
                'description' => 'الحفاظ على العميل وتكرار الشراء',
                'order' => 5,
                'conversion_weight' => 1.2
            ),
            'advocacy' => array(
                'name' => 'الدعوة',
                'description' => 'العميل يروج للمنتج أو الخدمة',
                'order' => 6,
                'conversion_weight' => 1.5
            )
        );
    }

    /**
     * تهيئة أنواع نقاط اللمس
     */
    private function initializeTouchpointTypes() {
        $this->touchpoint_types = array(
            'website' => array(
                'name' => 'الموقع الإلكتروني',
                'engagement_value' => 3,
                'activities' => array('page_view', 'form_submission', 'download')
            ),
            'email' => array(
                'name' => 'البريد الإلكتروني',
                'engagement_value' => 4,
                'activities' => array('email_open', 'email_click', 'email_reply')
            ),
            'social' => array(
                'name' => 'وسائل التواصل الاجتماعي',
                'engagement_value' => 2,
                'activities' => array('like', 'share', 'comment', 'follow')
            ),
            'phone' => array(
                'name' => 'الهاتف',
                'engagement_value' => 8,
                'activities' => array('inbound_call', 'outbound_call', 'voicemail')
            ),
            'store' => array(
                'name' => 'المتجر',
                'engagement_value' => 10,
                'activities' => array('visit', 'purchase', 'consultation')
            ),
            'mobile' => array(
                'name' => 'التطبيق المحمول',
                'engagement_value' => 5,
                'activities' => array('app_open', 'feature_use', 'push_notification')
            ),
            'referral' => array(
                'name' => 'الإحالة',
                'engagement_value' => 9,
                'activities' => array('referral_made', 'referral_converted')
            ),
            'advertising' => array(
                'name' => 'الإعلانات',
                'engagement_value' => 1,
                'activities' => array('ad_view', 'ad_click', 'ad_conversion')
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

        $filter_data['filter_customer'] = isset($this->request->get['customer']) ? $this->request->get['customer'] : '';
        $filter_data['filter_company'] = isset($this->request->get['company']) ? $this->request->get['company'] : '';
        $filter_data['filter_stage'] = isset($this->request->get['stage']) ? $this->request->get['stage'] : '';
        $filter_data['filter_status'] = isset($this->request->get['status']) ? $this->request->get['status'] : '';
        $filter_data['filter_health'] = isset($this->request->get['health']) ? $this->request->get['health'] : '';
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
     * تنسيق الرحلات للAPI
     */
    private function formatJourneysForApi($journeys) {
        $formatted = array();

        foreach ($journeys as $journey) {
            $formatted[] = $this->formatJourneyForApi($journey);
        }

        return $formatted;
    }

    /**
     * تنسيق رحلة واحدة للAPI
     */
    private function formatJourneyForApi($journey) {
        return array(
            'id' => (int)$journey['journey_id'],
            'customer' => array(
                'id' => (int)$journey['customer_id'],
                'name' => trim($journey['firstname'] . ' ' . $journey['lastname']),
                'email' => $journey['email'],
                'phone' => $journey['telephone'],
                'company' => $journey['company']
            ),
            'current_stage' => $journey['current_stage'],
            'stage_info' => $this->journey_stages[$journey['current_stage']] ?? null,
            'estimated_value' => (float)$journey['estimated_value'],
            'conversion_probability' => (int)$journey['conversion_probability'],
            'health_score' => (int)$journey['health_score'],
            'health_category' => $this->getHealthCategory($journey['health_score']),
            'status' => $journey['status'],
            'assigned_to' => array(
                'id' => (int)$journey['assigned_to'],
                'name' => trim($journey['assigned_firstname'] . ' ' . $journey['assigned_lastname'])
            ),
            'dates' => array(
                'start' => $journey['start_date'],
                'expected_close' => $journey['expected_close_date'],
                'completion' => $journey['completion_date'],
                'created' => $journey['date_created'],
                'modified' => $journey['date_modified']
            ),
            'touchpoint_count' => (int)$journey['touchpoint_count'],
            'last_touchpoint' => $journey['last_touchpoint'],
            'days_since_update' => (int)$journey['days_since_update'],
            'notes' => $journey['notes']
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
     * التحقق من صحة بيانات الرحلة
     */
    private function validateJourneyData($data) {
        $errors = array();

        if (empty($data['customer_id'])) {
            $errors[] = 'Customer ID is required';
        }

        if (empty($data['current_stage'])) {
            $errors[] = 'Current stage is required';
        } elseif (!isset($this->journey_stages[$data['current_stage']])) {
            $errors[] = 'Invalid stage specified';
        }

        if (isset($data['estimated_value']) && !is_numeric($data['estimated_value'])) {
            $errors[] = 'Estimated value must be numeric';
        }

        if (isset($data['conversion_probability']) && (!is_numeric($data['conversion_probability']) || $data['conversion_probability'] < 0 || $data['conversion_probability'] > 100)) {
            $errors[] = 'Conversion probability must be between 0 and 100';
        }

        if (isset($data['health_score']) && (!is_numeric($data['health_score']) || $data['health_score'] < 0 || $data['health_score'] > 100)) {
            $errors[] = 'Health score must be between 0 and 100';
        }

        return array(
            'valid' => empty($errors),
            'errors' => $errors
        );
    }

    /**
     * التحقق من صحة بيانات نقطة اللمس
     */
    private function validateTouchpointData($data) {
        $errors = array();

        if (empty($data['touchpoint_type'])) {
            $errors[] = 'Touchpoint type is required';
        } elseif (!isset($this->touchpoint_types[$data['touchpoint_type']])) {
            $errors[] = 'Invalid touchpoint type specified';
        }

        if (empty($data['activity_type'])) {
            $errors[] = 'Activity type is required';
        }

        if (empty($data['description'])) {
            $errors[] = 'Description is required';
        }

        if (isset($data['engagement_value']) && (!is_numeric($data['engagement_value']) || $data['engagement_value'] < 1 || $data['engagement_value'] > 10)) {
            $errors[] = 'Engagement value must be between 1 and 10';
        }

        return array(
            'valid' => empty($errors),
            'errors' => $errors
        );
    }

    /**
     * إعداد بيانات الرحلة
     */
    private function prepareJourneyData($input_data, $user_id) {
        return array(
            'customer_id' => (int)$input_data['customer_id'],
            'current_stage' => $input_data['current_stage'],
            'start_date' => isset($input_data['start_date']) ? $input_data['start_date'] : date('Y-m-d H:i:s'),
            'expected_close_date' => isset($input_data['expected_close_date']) ? $input_data['expected_close_date'] : date('Y-m-d', strtotime('+30 days')),
            'estimated_value' => isset($input_data['estimated_value']) ? (float)$input_data['estimated_value'] : 0,
            'conversion_probability' => isset($input_data['conversion_probability']) ? (int)$input_data['conversion_probability'] : $this->journey_stages[$input_data['current_stage']]['conversion_weight'] * 100,
            'health_score' => isset($input_data['health_score']) ? (int)$input_data['health_score'] : 75,
            'status' => isset($input_data['status']) ? $input_data['status'] : 'active',
            'assigned_to' => isset($input_data['assigned_to']) ? (int)$input_data['assigned_to'] : $user_id,
            'notes' => isset($input_data['notes']) ? $input_data['notes'] : '',
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
