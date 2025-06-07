<?php
/**
 * API متقدم لتوقعات المبيعات
 * Advanced Sales Forecast API Controller
 *
 * الهدف: توفير واجهة برمجة تطبيقات متقدمة لنظام توقعات المبيعات
 * الميزات: RESTful API، خوارزميات توقع متطورة، تحليلات في الوقت الفعلي
 *
 * @author ERP Team
 * @version 1.0
 * @since 2024
 */

class ControllerApiSalesForecast extends Controller {

    private $api_version = '1.0';
    private $rate_limit = 500; // requests per hour
    private $forecast_algorithms = array();

    /**
     * تهيئة API Controller
     */
    public function __construct($registry) {
        parent::__construct($registry);

        // تحميل النماذج المطلوبة
        $this->load->model('crm/sales_forecast');
        $this->load->model('api/authentication');
        $this->load->model('api/rate_limit');

        // تحميل مكتبات المساعدة
        $this->load->language('api/sales_forecast');
        $this->load->helper('api');

        // تهيئة خوارزميات التوقع
        $this->initializeForecastAlgorithms();

        // إعداد headers للAPI
        $this->setupApiHeaders();
    }

    /**
     * الحصول على قائمة التوقعات
     * GET /api/sales-forecast/forecasts
     */
    public function getForecasts() {
        try {
            // التحقق من المصادقة
            $auth_result = $this->authenticateRequest();
            if (!$auth_result['success']) {
                return $this->sendErrorResponse(401, 'Unauthorized', $auth_result['error']);
            }

            // معالجة المعاملات
            $filter_data = $this->processApiFilters();

            // الحصول على البيانات
            $forecasts = $this->model_crm_sales_forecast->getForecasts($filter_data);
            $total = $this->model_crm_sales_forecast->getTotalForecasts($filter_data);

            // تنسيق البيانات للAPI
            $formatted_forecasts = $this->formatForecastsForApi($forecasts);

            // إعداد الاستجابة
            $response = array(
                'success' => true,
                'data' => $formatted_forecasts,
                'meta' => array(
                    'total' => $total,
                    'count' => count($formatted_forecasts),
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
     * إنشاء توقع جديد
     * POST /api/sales-forecast/forecasts
     */
    public function createForecast() {
        try {
            // التحقق من المصادقة والصلاحيات
            $auth_result = $this->authenticateRequest();
            if (!$auth_result['success']) {
                return $this->sendErrorResponse(401, 'Unauthorized', $auth_result['error']);
            }

            if (!$this->hasPermission($auth_result['user_id'], 'forecast_create')) {
                return $this->sendErrorResponse(403, 'Forbidden', 'Insufficient permissions');
            }

            // الحصول على البيانات
            $input_data = $this->getJsonInput();

            // التحقق من صحة البيانات
            $validation_result = $this->validateForecastData($input_data);
            if (!$validation_result['valid']) {
                return $this->sendErrorResponse(422, 'Validation Error', $validation_result['errors']);
            }

            // جمع البيانات التاريخية
            $historical_data = $this->model_crm_sales_forecast->getHistoricalSalesData(
                $input_data['period'],
                $input_data['data_points'] ?? 24
            );

            if (empty($historical_data)) {
                return $this->sendErrorResponse(400, 'Insufficient Data', 'Not enough historical data for forecasting');
            }

            // تطبيق خوارزمية التوقع
            $algorithm = $input_data['algorithm'] ?? 'linear';
            $horizon = $input_data['horizon'] ?? 30;
            $confidence_level = $input_data['confidence_level'] ?? 95;

            $forecast_result = $this->applyForecastAlgorithm($algorithm, $historical_data, $horizon, $confidence_level);

            // حفظ التوقع
            $forecast_data = array(
                'period' => $input_data['period'],
                'forecast_type' => $input_data['forecast_type'],
                'method' => $algorithm,
                'predicted_amount' => $forecast_result['predicted_amount'],
                'confidence_level' => $confidence_level,
                'confidence_interval_lower' => $forecast_result['confidence_interval']['lower'],
                'confidence_interval_upper' => $forecast_result['confidence_interval']['upper'],
                'start_date' => $forecast_result['start_date'],
                'end_date' => $forecast_result['end_date'],
                'parameters' => json_encode($forecast_result['parameters']),
                'created_by' => $auth_result['user_id']
            );

            $forecast_id = $this->model_crm_sales_forecast->addForecast($forecast_data);

            // الحصول على التوقع المحفوظ
            $saved_forecast = $this->model_crm_sales_forecast->getForecast($forecast_id);
            $formatted_forecast = $this->formatForecastForApi($saved_forecast);

            $response = array(
                'success' => true,
                'message' => 'Forecast created successfully',
                'data' => $formatted_forecast,
                'algorithm_details' => $forecast_result,
                'api_version' => $this->api_version,
                'timestamp' => date('c')
            );

            return $this->sendSuccessResponse($response, 201);

        } catch (Exception $e) {
            return $this->sendErrorResponse(500, 'Internal Server Error', $e->getMessage());
        }
    }

    /**
     * الحصول على توقع محدد
     * GET /api/sales-forecast/forecasts/{id}
     */
    public function getForecast() {
        try {
            // التحقق من المصادقة
            $auth_result = $this->authenticateRequest();
            if (!$auth_result['success']) {
                return $this->sendErrorResponse(401, 'Unauthorized', $auth_result['error']);
            }

            $forecast_id = isset($this->request->get['id']) ? (int)$this->request->get['id'] : 0;

            if (!$forecast_id) {
                return $this->sendErrorResponse(400, 'Bad Request', 'Forecast ID is required');
            }

            // الحصول على التوقع
            $forecast = $this->model_crm_sales_forecast->getForecast($forecast_id);

            if (!$forecast) {
                return $this->sendErrorResponse(404, 'Not Found', 'Forecast not found');
            }

            // تنسيق البيانات
            $formatted_forecast = $this->formatForecastForApi($forecast);

            // إضافة تفاصيل إضافية
            $formatted_forecast['performance_metrics'] = $this->calculateForecastPerformance($forecast);
            $formatted_forecast['validation_status'] = $this->getForecastValidationStatus($forecast);

            $response = array(
                'success' => true,
                'data' => $formatted_forecast,
                'api_version' => $this->api_version,
                'timestamp' => date('c')
            );

            return $this->sendSuccessResponse($response);

        } catch (Exception $e) {
            return $this->sendErrorResponse(500, 'Internal Server Error', $e->getMessage());
        }
    }

    /**
     * التحقق من صحة التوقع
     * POST /api/sales-forecast/forecasts/{id}/validate
     */
    public function validateForecast() {
        try {
            // التحقق من المصادقة والصلاحيات
            $auth_result = $this->authenticateRequest();
            if (!$auth_result['success']) {
                return $this->sendErrorResponse(401, 'Unauthorized', $auth_result['error']);
            }

            if (!$this->hasPermission($auth_result['user_id'], 'forecast_validate')) {
                return $this->sendErrorResponse(403, 'Forbidden', 'Insufficient permissions');
            }

            $forecast_id = isset($this->request->get['id']) ? (int)$this->request->get['id'] : 0;

            if (!$forecast_id) {
                return $this->sendErrorResponse(400, 'Bad Request', 'Forecast ID is required');
            }

            // الحصول على التوقع
            $forecast = $this->model_crm_sales_forecast->getForecast($forecast_id);

            if (!$forecast) {
                return $this->sendErrorResponse(404, 'Not Found', 'Forecast not found');
            }

            // الحصول على البيانات الفعلية
            $actual_data = $this->model_crm_sales_forecast->getActualSalesData(
                $forecast['start_date'],
                $forecast['end_date'],
                $forecast['forecast_type']
            );

            // حساب مقاييس الدقة
            $validation_results = $this->calculateValidationMetrics($forecast, $actual_data);

            // تحديث التوقع
            $this->model_crm_sales_forecast->updateForecastValidation($forecast_id, $validation_results);

            $response = array(
                'success' => true,
                'message' => 'Forecast validated successfully',
                'data' => array(
                    'forecast_id' => $forecast_id,
                    'predicted_amount' => (float)$forecast['predicted_amount'],
                    'actual_amount' => (float)$actual_data,
                    'accuracy' => $validation_results['accuracy'],
                    'variance' => $validation_results['variance'],
                    'absolute_error' => $validation_results['absolute_error'],
                    'percentage_error' => $validation_results['percentage_error'],
                    'validation_date' => date('c')
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
     * مقارنة خوارزميات التوقع
     * POST /api/sales-forecast/compare-algorithms
     */
    public function compareAlgorithms() {
        try {
            // التحقق من المصادقة
            $auth_result = $this->authenticateRequest();
            if (!$auth_result['success']) {
                return $this->sendErrorResponse(401, 'Unauthorized', $auth_result['error']);
            }

            // الحصول على المعاملات
            $input_data = $this->getJsonInput();
            $algorithms = $input_data['algorithms'] ?? array_keys($this->forecast_algorithms);
            $period = $input_data['period'] ?? 'monthly';
            $horizon = $input_data['horizon'] ?? 30;

            // جمع البيانات التاريخية
            $historical_data = $this->model_crm_sales_forecast->getHistoricalSalesData($period, 24);

            if (empty($historical_data)) {
                return $this->sendErrorResponse(400, 'Insufficient Data', 'Not enough historical data for comparison');
            }

            $comparison_results = array();

            foreach ($algorithms as $algorithm) {
                if (isset($this->forecast_algorithms[$algorithm])) {
                    // تطبيق الخوارزمية
                    $result = $this->applyForecastAlgorithm($algorithm, $historical_data, $horizon, 95);

                    // حساب مقاييس الأداء
                    $performance = $this->calculateAlgorithmPerformance($algorithm, $historical_data);

                    $comparison_results[$algorithm] = array(
                        'name' => $this->forecast_algorithms[$algorithm]['name'],
                        'description' => $this->forecast_algorithms[$algorithm]['description'],
                        'complexity' => $this->forecast_algorithms[$algorithm]['complexity'],
                        'predicted_amount' => $result['predicted_amount'],
                        'confidence_interval' => $result['confidence_interval'],
                        'performance_metrics' => $performance,
                        'execution_time' => $result['execution_time'],
                        'parameters_used' => $result['parameters']
                    );
                }
            }

            // ترتيب النتائج حسب الدقة
            uasort($comparison_results, function($a, $b) {
                return $b['performance_metrics']['accuracy'] <=> $a['performance_metrics']['accuracy'];
            });

            $response = array(
                'success' => true,
                'data' => array(
                    'comparison_results' => $comparison_results,
                    'best_algorithm' => array_key_first($comparison_results),
                    'data_period' => $period,
                    'forecast_horizon' => $horizon,
                    'historical_data_points' => count($historical_data)
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
     * الحصول على إحصائيات التوقعات
     * GET /api/sales-forecast/statistics
     */
    public function getStatistics() {
        try {
            // التحقق من المصادقة
            $auth_result = $this->authenticateRequest();
            if (!$auth_result['success']) {
                return $this->sendErrorResponse(401, 'Unauthorized', $auth_result['error']);
            }

            // الحصول على الإحصائيات
            $statistics = $this->model_crm_sales_forecast->getForecastStatistics();
            $accuracy_trend = $this->model_crm_sales_forecast->getAccuracyTrendData(30);
            $method_comparison = $this->model_crm_sales_forecast->getMethodComparisonData();
            $performance_summary = $this->model_crm_sales_forecast->getMonthlyPerformanceSummary(12);

            $response = array(
                'success' => true,
                'data' => array(
                    'overview' => $statistics,
                    'accuracy_trend' => $accuracy_trend,
                    'method_comparison' => $method_comparison,
                    'performance_summary' => $performance_summary,
                    'available_algorithms' => $this->forecast_algorithms
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
     * تهيئة خوارزميات التوقع
     */
    private function initializeForecastAlgorithms() {
        $this->forecast_algorithms = array(
            'linear' => array(
                'name' => 'Linear Regression',
                'description' => 'Simple linear trend forecasting',
                'complexity' => 'Low',
                'accuracy_range' => '70-85%'
            ),
            'moving_average' => array(
                'name' => 'Moving Average',
                'description' => 'Average of recent data points',
                'complexity' => 'Low',
                'accuracy_range' => '65-80%'
            ),
            'exponential' => array(
                'name' => 'Exponential Smoothing',
                'description' => 'Weighted average with exponential decay',
                'complexity' => 'Medium',
                'accuracy_range' => '75-90%'
            ),
            'seasonal' => array(
                'name' => 'Seasonal Decomposition',
                'description' => 'Accounts for seasonal patterns',
                'complexity' => 'Medium',
                'accuracy_range' => '80-92%'
            ),
            'arima' => array(
                'name' => 'ARIMA Model',
                'description' => 'Advanced time series analysis',
                'complexity' => 'High',
                'accuracy_range' => '85-95%'
            ),
            'neural' => array(
                'name' => 'Neural Network',
                'description' => 'AI-powered pattern recognition',
                'complexity' => 'Very High',
                'accuracy_range' => '90-98%'
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

        $filter_data['filter_period'] = isset($this->request->get['period']) ? $this->request->get['period'] : '';
        $filter_data['filter_type'] = isset($this->request->get['type']) ? $this->request->get['type'] : '';
        $filter_data['filter_method'] = isset($this->request->get['method']) ? $this->request->get['method'] : '';
        $filter_data['filter_status'] = isset($this->request->get['status']) ? $this->request->get['status'] : '';
        $filter_data['filter_performance'] = isset($this->request->get['performance']) ? $this->request->get['performance'] : '';

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
     * تنسيق التوقعات للAPI
     */
    private function formatForecastsForApi($forecasts) {
        $formatted = array();

        foreach ($forecasts as $forecast) {
            $formatted[] = $this->formatForecastForApi($forecast);
        }

        return $formatted;
    }

    /**
     * تنسيق توقع واحد للAPI
     */
    private function formatForecastForApi($forecast) {
        return array(
            'id' => (int)$forecast['forecast_id'],
            'period' => $forecast['period'],
            'forecast_type' => $forecast['forecast_type'],
            'method' => $forecast['method'],
            'predicted_amount' => (float)$forecast['predicted_amount'],
            'actual_amount' => $forecast['actual_amount'] ? (float)$forecast['actual_amount'] : null,
            'confidence_level' => (int)$forecast['confidence_level'],
            'confidence_interval' => array(
                'lower' => (float)$forecast['confidence_interval_lower'],
                'upper' => (float)$forecast['confidence_interval_upper']
            ),
            'accuracy' => $forecast['accuracy'] ? round($forecast['accuracy'], 2) : null,
            'variance' => $forecast['variance'] ? round($forecast['variance'], 2) : null,
            'status' => $forecast['status'],
            'dates' => array(
                'start' => $forecast['start_date'],
                'end' => $forecast['end_date'],
                'created' => $forecast['date_created'],
                'modified' => $forecast['date_modified'],
                'validated' => $forecast['validation_date']
            ),
            'created_by' => array(
                'id' => (int)$forecast['created_by'],
                'name' => trim($forecast['firstname'] . ' ' . $forecast['lastname'])
            ),
            'parameters' => $forecast['parameters'] ? json_decode($forecast['parameters'], true) : null
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
     * التحقق من صحة بيانات التوقع
     */
    private function validateForecastData($data) {
        $errors = array();

        if (empty($data['period'])) {
            $errors[] = 'Period is required';
        }

        if (empty($data['forecast_type'])) {
            $errors[] = 'Forecast type is required';
        }

        if (isset($data['algorithm']) && !isset($this->forecast_algorithms[$data['algorithm']])) {
            $errors[] = 'Invalid algorithm specified';
        }

        if (isset($data['horizon']) && (!is_numeric($data['horizon']) || $data['horizon'] <= 0)) {
            $errors[] = 'Horizon must be a positive number';
        }

        if (isset($data['confidence_level']) && (!is_numeric($data['confidence_level']) || $data['confidence_level'] < 50 || $data['confidence_level'] > 99)) {
            $errors[] = 'Confidence level must be between 50 and 99';
        }

        return array(
            'valid' => empty($errors),
            'errors' => $errors
        );
    }

    /**
     * تطبيق خوارزمية التوقع
     */
    private function applyForecastAlgorithm($algorithm, $historical_data, $horizon, $confidence_level) {
        $start_time = microtime(true);

        switch ($algorithm) {
            case 'linear':
                $result = $this->applyLinearRegression($historical_data, $horizon, $confidence_level);
                break;
            case 'moving_average':
                $result = $this->applyMovingAverage($historical_data, $horizon, $confidence_level);
                break;
            case 'exponential':
                $result = $this->applyExponentialSmoothing($historical_data, $horizon, $confidence_level);
                break;
            case 'seasonal':
                $result = $this->applySeasonalDecomposition($historical_data, $horizon, $confidence_level);
                break;
            case 'arima':
                $result = $this->applyARIMA($historical_data, $horizon, $confidence_level);
                break;
            case 'neural':
                $result = $this->applyNeuralNetwork($historical_data, $horizon, $confidence_level);
                break;
            default:
                $result = $this->applyLinearRegression($historical_data, $horizon, $confidence_level);
        }

        $execution_time = microtime(true) - $start_time;
        $result['execution_time'] = round($execution_time * 1000, 2); // milliseconds

        return $result;
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
