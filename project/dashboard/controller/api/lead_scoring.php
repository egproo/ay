<?php
/**
 * API متقدم لتقييم العملاء المحتملين
 * Advanced Lead Scoring API Controller
 *
 * الهدف: توفير واجهة برمجة تطبيقات متقدمة لنظام تقييم العملاء المحتملين
 * الميزات: RESTful API، مصادقة متقدمة، تحليلات في الوقت الفعلي
 *
 * @author ERP Team
 * @version 1.0
 * @since 2024
 */

class ControllerApiLeadScoring extends Controller {

    private $api_version = '1.0';
    private $rate_limit = 1000; // requests per hour
    private $allowed_methods = array('GET', 'POST', 'PUT', 'DELETE');

    /**
     * تهيئة API Controller
     */
    public function __construct($registry) {
        parent::__construct($registry);

        // تحميل النماذج المطلوبة
        $this->load->model('crm/lead_scoring');
        $this->load->model('api/authentication');
        $this->load->model('api/rate_limit');

        // تحميل مكتبات المساعدة
        $this->load->language('api/lead_scoring');
        $this->load->helper('api');

        // إعداد headers للAPI
        $this->setupApiHeaders();
    }

    /**
     * الحصول على قائمة العملاء المحتملين مع النقاط
     * GET /api/lead-scoring/leads
     */
    public function getLeads() {
        try {
            // التحقق من المصادقة
            $auth_result = $this->authenticateRequest();
            if (!$auth_result['success']) {
                return $this->sendErrorResponse(401, 'Unauthorized', $auth_result['error']);
            }

            // التحقق من حد المعدل
            $rate_limit_result = $this->checkRateLimit($auth_result['api_key']);
            if (!$rate_limit_result['success']) {
                return $this->sendErrorResponse(429, 'Too Many Requests', 'Rate limit exceeded');
            }

            // معالجة المعاملات
            $filter_data = $this->processApiFilters();

            // الحصول على البيانات
            $leads = $this->model_crm_lead_scoring->getLeadsWithScores($filter_data);
            $total = $this->model_crm_lead_scoring->getTotalLeadsWithScores($filter_data);

            // تنسيق البيانات للAPI
            $formatted_leads = $this->formatLeadsForApi($leads);

            // إعداد الاستجابة
            $response = array(
                'success' => true,
                'data' => $formatted_leads,
                'meta' => array(
                    'total' => $total,
                    'count' => count($formatted_leads),
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
     * الحصول على عميل محتمل محدد مع النقاط
     * GET /api/lead-scoring/leads/{id}
     */
    public function getLead() {
        try {
            // التحقق من المصادقة
            $auth_result = $this->authenticateRequest();
            if (!$auth_result['success']) {
                return $this->sendErrorResponse(401, 'Unauthorized', $auth_result['error']);
            }

            $lead_id = isset($this->request->get['id']) ? (int)$this->request->get['id'] : 0;

            if (!$lead_id) {
                return $this->sendErrorResponse(400, 'Bad Request', 'Lead ID is required');
            }

            // الحصول على البيانات
            $lead = $this->model_crm_lead_scoring->getLeadWithScore($lead_id);

            if (!$lead) {
                return $this->sendErrorResponse(404, 'Not Found', 'Lead not found');
            }

            // تنسيق البيانات للAPI
            $formatted_lead = $this->formatLeadForApi($lead);

            // إضافة تفاصيل إضافية
            $formatted_lead['score_breakdown'] = $this->getScoreBreakdown($lead_id);
            $formatted_lead['activity_summary'] = $this->getActivitySummary($lead_id);

            $response = array(
                'success' => true,
                'data' => $formatted_lead,
                'api_version' => $this->api_version,
                'timestamp' => date('c')
            );

            return $this->sendSuccessResponse($response);

        } catch (Exception $e) {
            return $this->sendErrorResponse(500, 'Internal Server Error', $e->getMessage());
        }
    }

    /**
     * إضافة عميل محتمل جديد
     * POST /api/lead-scoring/leads
     */
    public function createLead() {
        try {
            // التحقق من المصادقة والصلاحيات
            $auth_result = $this->authenticateRequest();
            if (!$auth_result['success']) {
                return $this->sendErrorResponse(401, 'Unauthorized', $auth_result['error']);
            }

            if (!$this->hasPermission($auth_result['user_id'], 'lead_create')) {
                return $this->sendErrorResponse(403, 'Forbidden', 'Insufficient permissions');
            }

            // التحقق من صحة البيانات
            $input_data = $this->getJsonInput();
            $validation_result = $this->validateLeadData($input_data);

            if (!$validation_result['valid']) {
                return $this->sendErrorResponse(422, 'Validation Error', $validation_result['errors']);
            }

            // إضافة العميل المحتمل
            $lead_data = $this->prepareLeadData($input_data, $auth_result['user_id']);
            $lead_id = $this->model_crm_lead_scoring->addLead($lead_data);

            // حساب النقاط الأولية
            $initial_score = $this->calculateInitialScore($lead_id);
            $this->model_crm_lead_scoring->updateLeadScore($lead_id, $initial_score);

            // الحصول على البيانات المحدثة
            $lead = $this->model_crm_lead_scoring->getLeadWithScore($lead_id);
            $formatted_lead = $this->formatLeadForApi($lead);

            $response = array(
                'success' => true,
                'message' => 'Lead created successfully',
                'data' => $formatted_lead,
                'api_version' => $this->api_version,
                'timestamp' => date('c')
            );

            return $this->sendSuccessResponse($response, 201);

        } catch (Exception $e) {
            return $this->sendErrorResponse(500, 'Internal Server Error', $e->getMessage());
        }
    }

    /**
     * تحديث نقاط العميل المحتمل
     * PUT /api/lead-scoring/leads/{id}/score
     */
    public function updateScore() {
        try {
            // التحقق من المصادقة والصلاحيات
            $auth_result = $this->authenticateRequest();
            if (!$auth_result['success']) {
                return $this->sendErrorResponse(401, 'Unauthorized', $auth_result['error']);
            }

            $lead_id = isset($this->request->get['id']) ? (int)$this->request->get['id'] : 0;

            if (!$lead_id) {
                return $this->sendErrorResponse(400, 'Bad Request', 'Lead ID is required');
            }

            // التحقق من وجود العميل المحتمل
            $lead = $this->model_crm_lead_scoring->getLeadWithScore($lead_id);
            if (!$lead) {
                return $this->sendErrorResponse(404, 'Not Found', 'Lead not found');
            }

            // إعادة حساب النقاط
            $new_score = $this->recalculateScore($lead_id);
            $this->model_crm_lead_scoring->updateLeadScore($lead_id, $new_score);

            // الحصول على البيانات المحدثة
            $updated_lead = $this->model_crm_lead_scoring->getLeadWithScore($lead_id);
            $formatted_lead = $this->formatLeadForApi($updated_lead);

            $response = array(
                'success' => true,
                'message' => 'Score updated successfully',
                'data' => $formatted_lead,
                'score_change' => array(
                    'old_score' => $lead['total_score'],
                    'new_score' => $new_score['total_score'],
                    'change' => $new_score['total_score'] - $lead['total_score']
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
     * الحصول على إحصائيات التقييم
     * GET /api/lead-scoring/statistics
     */
    public function getStatistics() {
        try {
            // التحقق من المصادقة
            $auth_result = $this->authenticateRequest();
            if (!$auth_result['success']) {
                return $this->sendErrorResponse(401, 'Unauthorized', $auth_result['error']);
            }

            // الحصول على الإحصائيات
            $statistics = $this->model_crm_lead_scoring->getScoringStatistics();
            $score_distribution = $this->model_crm_lead_scoring->getScoreDistribution();
            $top_sources = $this->model_crm_lead_scoring->getTopSources();
            $score_trend = $this->model_crm_lead_scoring->getScoreTrend(30);

            $response = array(
                'success' => true,
                'data' => array(
                    'overview' => $statistics,
                    'score_distribution' => $score_distribution,
                    'top_sources' => $top_sources,
                    'score_trend' => $score_trend,
                    'performance_metrics' => $this->calculatePerformanceMetrics()
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
     * البحث في العملاء المحتملين
     * GET /api/lead-scoring/search
     */
    public function searchLeads() {
        try {
            // التحقق من المصادقة
            $auth_result = $this->authenticateRequest();
            if (!$auth_result['success']) {
                return $this->sendErrorResponse(401, 'Unauthorized', $auth_result['error']);
            }

            $search_term = isset($this->request->get['q']) ? $this->request->get['q'] : '';
            $limit = isset($this->request->get['limit']) ? (int)$this->request->get['limit'] : 20;

            if (empty($search_term)) {
                return $this->sendErrorResponse(400, 'Bad Request', 'Search term is required');
            }

            if (strlen($search_term) < 3) {
                return $this->sendErrorResponse(400, 'Bad Request', 'Search term must be at least 3 characters');
            }

            // البحث
            $results = $this->searchLeadsAdvanced($search_term, $limit);

            $response = array(
                'success' => true,
                'data' => $results,
                'meta' => array(
                    'search_term' => $search_term,
                    'count' => count($results),
                    'limit' => $limit
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
     * تحويل العميل المحتمل إلى عميل
     * POST /api/lead-scoring/leads/{id}/convert
     */
    public function convertLead() {
        try {
            // التحقق من المصادقة والصلاحيات
            $auth_result = $this->authenticateRequest();
            if (!$auth_result['success']) {
                return $this->sendErrorResponse(401, 'Unauthorized', $auth_result['error']);
            }

            if (!$this->hasPermission($auth_result['user_id'], 'lead_convert')) {
                return $this->sendErrorResponse(403, 'Forbidden', 'Insufficient permissions');
            }

            $lead_id = isset($this->request->get['id']) ? (int)$this->request->get['id'] : 0;

            if (!$lead_id) {
                return $this->sendErrorResponse(400, 'Bad Request', 'Lead ID is required');
            }

            // التحقق من وجود العميل المحتمل
            $lead = $this->model_crm_lead_scoring->getLeadWithScore($lead_id);
            if (!$lead) {
                return $this->sendErrorResponse(404, 'Not Found', 'Lead not found');
            }

            if ($lead['status'] === 'converted') {
                return $this->sendErrorResponse(409, 'Conflict', 'Lead already converted');
            }

            // الحصول على بيانات التحويل
            $input_data = $this->getJsonInput();
            $customer_data = $this->prepareCustomerData($input_data, $lead);

            // تحويل العميل المحتمل
            $customer_id = $this->model_crm_lead_scoring->convertLeadToCustomer($lead_id, $customer_data);

            if (!$customer_id) {
                return $this->sendErrorResponse(500, 'Internal Server Error', 'Failed to convert lead');
            }

            $response = array(
                'success' => true,
                'message' => 'Lead converted successfully',
                'data' => array(
                    'lead_id' => $lead_id,
                    'customer_id' => $customer_id,
                    'conversion_date' => date('c'),
                    'final_score' => $lead['total_score']
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
     * إعداد headers للAPI
     */
    private function setupApiHeaders() {
        header('Content-Type: application/json; charset=utf-8');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-API-Key');
        header('X-API-Version: ' . $this->api_version);
        header('X-Rate-Limit: ' . $this->rate_limit);

        // معالجة طلبات OPTIONS للCORS
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
     * الحصول على API Key من الطلب
     */
    private function getApiKey() {
        // من header Authorization
        if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            $auth_header = $_SERVER['HTTP_AUTHORIZATION'];
            if (preg_match('/Bearer\s+(.*)$/i', $auth_header, $matches)) {
                return $matches[1];
            }
        }

        // من header X-API-Key
        if (isset($_SERVER['HTTP_X_API_KEY'])) {
            return $_SERVER['HTTP_X_API_KEY'];
        }

        // من query parameter
        if (isset($this->request->get['api_key'])) {
            return $this->request->get['api_key'];
        }

        return null;
    }

    /**
     * التحقق من حد المعدل
     */
    private function checkRateLimit($api_key) {
        return $this->model_api_rate_limit->checkLimit($api_key, $this->rate_limit);
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

        // الفلاتر الأساسية
        $filter_data['filter_name'] = isset($this->request->get['name']) ? $this->request->get['name'] : '';
        $filter_data['filter_email'] = isset($this->request->get['email']) ? $this->request->get['email'] : '';
        $filter_data['filter_company'] = isset($this->request->get['company']) ? $this->request->get['company'] : '';
        $filter_data['filter_source'] = isset($this->request->get['source']) ? $this->request->get['source'] : '';
        $filter_data['filter_status'] = isset($this->request->get['status']) ? $this->request->get['status'] : '';
        $filter_data['filter_score_range'] = isset($this->request->get['score_range']) ? $this->request->get['score_range'] : '';

        // فلاتر التاريخ
        $filter_data['filter_date_from'] = isset($this->request->get['date_from']) ? $this->request->get['date_from'] : '';
        $filter_data['filter_date_to'] = isset($this->request->get['date_to']) ? $this->request->get['date_to'] : '';

        // الترتيب
        $filter_data['sort'] = isset($this->request->get['sort']) ? $this->request->get['sort'] : 'total_score';
        $filter_data['order'] = isset($this->request->get['order']) ? strtoupper($this->request->get['order']) : 'DESC';

        // التصفح
        $filter_data['page'] = isset($this->request->get['page']) ? max(1, (int)$this->request->get['page']) : 1;
        $filter_data['limit'] = isset($this->request->get['limit']) ? min(100, max(1, (int)$this->request->get['limit'])) : 20;
        $filter_data['start'] = ($filter_data['page'] - 1) * $filter_data['limit'];

        return $filter_data;
    }

    /**
     * تنسيق العملاء المحتملين للAPI
     */
    private function formatLeadsForApi($leads) {
        $formatted = array();

        foreach ($leads as $lead) {
            $formatted[] = $this->formatLeadForApi($lead);
        }

        return $formatted;
    }

    /**
     * تنسيق عميل محتمل واحد للAPI
     */
    private function formatLeadForApi($lead) {
        return array(
            'id' => (int)$lead['lead_id'],
            'customer_name' => $lead['customer_name'],
            'email' => $lead['email'],
            'phone' => $lead['phone'],
            'company' => $lead['company'],
            'job_title' => $lead['job_title'],
            'source' => $lead['source'],
            'status' => $lead['status'],
            'estimated_value' => (float)$lead['estimated_value'],
            'probability' => (int)$lead['probability'],
            'scores' => array(
                'total' => (float)$lead['total_score'],
                'demographic' => (float)$lead['demographic_score'],
                'behavioral' => (float)$lead['behavioral_score'],
                'engagement' => (float)$lead['engagement_score'],
                'company' => (float)$lead['company_score'],
                'source' => (float)$lead['source_score']
            ),
            'priority' => $this->determinePriority($lead['total_score']),
            'conversion_probability' => $this->calculateConversionProbability($lead['total_score']),
            'assigned_to' => array(
                'id' => (int)$lead['assigned_to'],
                'name' => trim($lead['firstname'] . ' ' . $lead['lastname'])
            ),
            'dates' => array(
                'created' => $lead['date_created'],
                'modified' => $lead['date_modified'],
                'expected_close' => $lead['expected_close_date'],
                'score_calculated' => $lead['calculated_at']
            ),
            'activity_count' => (int)$lead['activity_count'],
            'last_activity' => $lead['last_activity']
        );
    }

    /**
     * الحصول على بيانات JSON من الطلب
     */
    private function getJsonInput() {
        $input = file_get_contents('php://input');
        return json_decode($input, true);
    }

    /**
     * التحقق من صحة بيانات العميل المحتمل
     */
    private function validateLeadData($data) {
        $errors = array();

        // التحقق من الحقول المطلوبة
        if (empty($data['customer_name'])) {
            $errors[] = 'Customer name is required';
        }

        if (empty($data['email'])) {
            $errors[] = 'Email is required';
        } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Invalid email format';
        }

        if (empty($data['source'])) {
            $errors[] = 'Source is required';
        }

        // التحقق من القيم الاختيارية
        if (isset($data['estimated_value']) && !is_numeric($data['estimated_value'])) {
            $errors[] = 'Estimated value must be numeric';
        }

        if (isset($data['probability']) && (!is_numeric($data['probability']) || $data['probability'] < 0 || $data['probability'] > 100)) {
            $errors[] = 'Probability must be between 0 and 100';
        }

        return array(
            'valid' => empty($errors),
            'errors' => $errors
        );
    }

    /**
     * إعداد بيانات العميل المحتمل
     */
    private function prepareLeadData($input_data, $user_id) {
        return array(
            'customer_name' => $input_data['customer_name'],
            'email' => $input_data['email'],
            'phone' => isset($input_data['phone']) ? $input_data['phone'] : '',
            'company' => isset($input_data['company']) ? $input_data['company'] : '',
            'job_title' => isset($input_data['job_title']) ? $input_data['job_title'] : '',
            'source' => $input_data['source'],
            'status' => isset($input_data['status']) ? $input_data['status'] : 'new',
            'assigned_to' => isset($input_data['assigned_to']) ? (int)$input_data['assigned_to'] : $user_id,
            'estimated_value' => isset($input_data['estimated_value']) ? (float)$input_data['estimated_value'] : 0,
            'probability' => isset($input_data['probability']) ? (int)$input_data['probability'] : 50,
            'expected_close_date' => isset($input_data['expected_close_date']) ? $input_data['expected_close_date'] : date('Y-m-d', strtotime('+30 days')),
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
