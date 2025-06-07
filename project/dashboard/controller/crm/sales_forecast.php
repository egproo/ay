<?php
/**
 * توقعات المبيعات (Sales Forecast Controller)
 *
 * الهدف: توقع وتحليل المبيعات المستقبلية بناءً على البيانات التاريخية والاتجاهات
 * الميزات: توقعات ذكية، تحليل اتجاهات، سيناريوهات متعددة، تكامل مع CRM
 * التكامل: مع المبيعات والعملاء المحتملين والتحليلات والذكاء الاصطناعي
 *
 * @author ERP Team
 * @version 1.0
 * @since 2024
 */

class ControllerCrmSalesForecast extends Controller {

    private $error = [];

    /**
     * الصفحة الرئيسية لتوقعات المبيعات
     */
    public function index() {
        $this->load->language('crm/sales_forecast');

        $this->document->setTitle($this->language->get('heading_title'));

        $data['breadcrumbs'] = [];

        $data['breadcrumbs'][] = [
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        ];

        $data['breadcrumbs'][] = [
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('crm/sales_forecast', 'user_token=' . $this->session->data['user_token'], true)
        ];

        $this->load->model('crm/sales_forecast');

        // إعداد المرشحات
        $filter_data = $this->getFilterData();

        // الحصول على التوقعات
        $forecasts = $this->model_crm_sales_forecast->getForecasts($filter_data);

        $data['forecasts'] = [];

        foreach ($forecasts as $forecast) {
            $accuracy_class = $this->getAccuracyClass($forecast['accuracy']);
            $confidence_class = $this->getConfidenceClass($forecast['confidence_level']);

            $data['forecasts'][] = [
                'forecast_id' => $forecast['forecast_id'],
                'period' => $forecast['period'],
                'period_text' => $this->getPeriodText($forecast['period']),
                'forecast_type' => $forecast['forecast_type'],
                'forecast_type_text' => $this->getForecastTypeText($forecast['forecast_type']),
                'predicted_amount' => number_format($forecast['predicted_amount'], 2),
                'actual_amount' => number_format($forecast['actual_amount'], 2),
                'variance' => number_format($forecast['variance'], 2),
                'variance_percentage' => number_format($forecast['variance_percentage'], 1) . '%',
                'accuracy' => number_format($forecast['accuracy'], 1) . '%',
                'accuracy_class' => $accuracy_class,
                'confidence_level' => number_format($forecast['confidence_level'], 1) . '%',
                'confidence_class' => $confidence_class,
                'method' => $forecast['method'],
                'method_text' => $this->getMethodText($forecast['method']),
                'created_by' => $forecast['created_by_name'],
                'date_created' => date($this->language->get('date_format_short'), strtotime($forecast['date_created'])),
                'view' => $this->url->link('crm/sales_forecast/view', 'user_token=' . $this->session->data['user_token'] . '&forecast_id=' . $forecast['forecast_id'], true),
                'edit' => $this->url->link('crm/sales_forecast/edit', 'user_token=' . $this->session->data['user_token'] . '&forecast_id=' . $forecast['forecast_id'], true),
                'compare' => $this->url->link('crm/sales_forecast/compare', 'user_token=' . $this->session->data['user_token'] . '&forecast_id=' . $forecast['forecast_id'], true)
            ];
        }

        // إعداد الروابط والأزرار
        $data['create'] = $this->url->link('crm/sales_forecast/create', 'user_token=' . $this->session->data['user_token'], true);
        $data['auto_generate'] = $this->url->link('crm/sales_forecast/autoGenerate', 'user_token=' . $this->session->data['user_token'], true);
        $data['export'] = $this->url->link('crm/sales_forecast/export', 'user_token=' . $this->session->data['user_token'], true);
        $data['analytics'] = $this->url->link('crm/sales_forecast/analytics', 'user_token=' . $this->session->data['user_token'], true);
        $data['scenarios'] = $this->url->link('crm/sales_forecast/scenarios', 'user_token=' . $this->session->data['user_token'], true);

        // إحصائيات سريعة
        $data['statistics'] = $this->getQuickStatistics();

        // الرسوم البيانية
        $data['charts'] = $this->getChartsData();

        // قوائم للفلاتر
        $data['periods'] = $this->getPeriods();
        $data['forecast_types'] = $this->getForecastTypes();
        $data['methods'] = $this->getForecastMethods();

        $data['user_token'] = $this->session->data['user_token'];
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('crm/sales_forecast_list', $data));
    }

    /**
     * إنشاء توقع جديد
     */
    public function create() {
        $this->load->language('crm/sales_forecast');

        $this->document->setTitle($this->language->get('heading_title_create'));

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
            $this->load->model('crm/sales_forecast');

            $forecast_id = $this->model_crm_sales_forecast->createForecast($this->request->post);

            // إضافة سجل في نشاط النظام
            $this->load->model('tool/activity_log');
            $this->model_tool_activity_log->addActivity('sales_forecast_create', 'crm', 'تم إنشاء توقع مبيعات جديد', $forecast_id);

            $this->session->data['success'] = $this->language->get('text_success_create');

            $this->response->redirect($this->url->link('crm/sales_forecast/view', 'user_token=' . $this->session->data['user_token'] . '&forecast_id=' . $forecast_id, true));
        }

        $this->getForm();
    }

    /**
     * عرض تفاصيل التوقع
     */
    public function view() {
        $this->load->language('crm/sales_forecast');

        if (isset($this->request->get['forecast_id'])) {
            $forecast_id = (int)$this->request->get['forecast_id'];

            $this->load->model('crm/sales_forecast');

            $forecast_info = $this->model_crm_sales_forecast->getForecast($forecast_id);

            if ($forecast_info) {
                $this->document->setTitle($this->language->get('heading_title_view') . ' - ' . $forecast_info['period']);

                // الحصول على تفاصيل التوقع
                $forecast_details = $this->model_crm_sales_forecast->getForecastDetails($forecast_id);

                // الحصول على البيانات التاريخية
                $historical_data = $this->model_crm_sales_forecast->getHistoricalData($forecast_info);

                // الحصول على مقارنات
                $comparisons = $this->model_crm_sales_forecast->getForecastComparisons($forecast_id);

                $data['forecast'] = $forecast_info;
                $data['forecast_details'] = $forecast_details;
                $data['historical_data'] = $historical_data;
                $data['comparisons'] = $comparisons;

                // حساب الإحصائيات
                $data['forecast_statistics'] = $this->calculateForecastStatistics($forecast_info, $historical_data);

                // الرسوم البيانية
                $data['forecast_charts'] = $this->getForecastCharts($forecast_id);

                $data['user_token'] = $this->session->data['user_token'];
                $data['header'] = $this->load->controller('common/header');
                $data['column_left'] = $this->load->controller('common/column_left');
                $data['footer'] = $this->load->controller('common/footer');

                $this->response->setOutput($this->load->view('crm/sales_forecast_view', $data));
            } else {
                $this->session->data['error'] = $this->language->get('error_not_found');
                $this->response->redirect($this->url->link('crm/sales_forecast', 'user_token=' . $this->session->data['user_token'], true));
            }
        } else {
            $this->response->redirect($this->url->link('crm/sales_forecast', 'user_token=' . $this->session->data['user_token'], true));
        }
    }

    /**
     * توليد توقعات تلقائية
     */
    public function autoGenerate() {
        $this->load->language('crm/sales_forecast');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateAutoGenerate()) {
            $this->load->model('crm/sales_forecast');

            $results = $this->model_crm_sales_forecast->generateAutoForecasts($this->request->post);

            if ($results) {
                $this->session->data['success'] = sprintf($this->language->get('text_success_auto_generate'), count($results));
            } else {
                $this->session->data['error'] = $this->language->get('error_auto_generate');
            }

            $this->response->redirect($this->url->link('crm/sales_forecast', 'user_token=' . $this->session->data['user_token'], true));
        }

        $this->load->model('crm/sales_forecast');

        $data['auto_methods'] = $this->getAutoGenerationMethods();
        $data['periods'] = $this->getAvailablePeriods();

        $data['user_token'] = $this->session->data['user_token'];
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('crm/sales_forecast_auto_generate', $data));
    }

    /**
     * تحليلات التوقعات
     */
    public function analytics() {
        $this->load->language('crm/sales_forecast');

        $this->document->setTitle($this->language->get('heading_title_analytics'));

        $this->load->model('crm/sales_forecast');

        // تحليلات شاملة
        $data['analytics'] = [
            'accuracy_trends' => $this->model_crm_sales_forecast->getAccuracyTrends(),
            'method_performance' => $this->model_crm_sales_forecast->getMethodPerformance(),
            'seasonal_patterns' => $this->model_crm_sales_forecast->getSeasonalPatterns(),
            'variance_analysis' => $this->model_crm_sales_forecast->getVarianceAnalysis(),
            'confidence_distribution' => $this->model_crm_sales_forecast->getConfidenceDistribution(),
            'forecast_vs_actual' => $this->model_crm_sales_forecast->getForecastVsActual()
        ];

        // مؤشرات الأداء الرئيسية
        $data['kpis'] = [
            'overall_accuracy' => $this->model_crm_sales_forecast->getOverallAccuracy(),
            'best_method' => $this->model_crm_sales_forecast->getBestPerformingMethod(),
            'prediction_reliability' => $this->model_crm_sales_forecast->getPredictionReliability(),
            'forecast_coverage' => $this->model_crm_sales_forecast->getForecastCoverage()
        ];

        $data['user_token'] = $this->session->data['user_token'];
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('crm/sales_forecast_analytics', $data));
    }

    /**
     * سيناريوهات التوقعات
     */
    public function scenarios() {
        $this->load->language('crm/sales_forecast');

        $this->document->setTitle($this->language->get('heading_title_scenarios'));

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateScenarios()) {
            $this->load->model('crm/sales_forecast');

            $scenarios = $this->model_crm_sales_forecast->generateScenarios($this->request->post);

            $data['scenarios'] = $scenarios;
            $data['scenario_comparison'] = $this->model_crm_sales_forecast->compareScenarios($scenarios);
        }

        $data['scenario_types'] = $this->getScenarioTypes();
        $data['adjustment_factors'] = $this->getAdjustmentFactors();

        $data['user_token'] = $this->session->data['user_token'];
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('crm/sales_forecast_scenarios', $data));
    }

    /**
     * دوال مساعدة
     */
    private function getFilterData() {
        $filter_data = [
            'filter_period' => $this->request->get['filter_period'] ?? '',
            'filter_type' => $this->request->get['filter_type'] ?? '',
            'filter_method' => $this->request->get['filter_method'] ?? '',
            'filter_accuracy' => $this->request->get['filter_accuracy'] ?? '',
            'sort' => $this->request->get['sort'] ?? 'date_created',
            'order' => $this->request->get['order'] ?? 'DESC',
            'start' => ($this->request->get['page'] ?? 1 - 1) * ($this->request->get['limit'] ?? 20),
            'limit' => $this->request->get['limit'] ?? 20,
            'page' => $this->request->get['page'] ?? 1
        ];

        return $filter_data;
    }

    private function getAccuracyClass($accuracy) {
        if ($accuracy >= 90) return 'success';
        if ($accuracy >= 80) return 'info';
        if ($accuracy >= 70) return 'warning';
        return 'danger';
    }

    private function getConfidenceClass($confidence) {
        if ($confidence >= 85) return 'success';
        if ($confidence >= 70) return 'warning';
        return 'danger';
    }

    private function getPeriodText($period) {
        $periods = [
            'daily' => $this->language->get('text_period_daily'),
            'weekly' => $this->language->get('text_period_weekly'),
            'monthly' => $this->language->get('text_period_monthly'),
            'quarterly' => $this->language->get('text_period_quarterly'),
            'yearly' => $this->language->get('text_period_yearly')
        ];

        return $periods[$period] ?? $period;
    }

    private function getForecastTypeText($type) {
        $types = [
            'revenue' => $this->language->get('text_type_revenue'),
            'units' => $this->language->get('text_type_units'),
            'customers' => $this->language->get('text_type_customers'),
            'orders' => $this->language->get('text_type_orders')
        ];

        return $types[$type] ?? $type;
    }

    private function getMethodText($method) {
        $methods = [
            'linear_regression' => $this->language->get('text_method_linear'),
            'moving_average' => $this->language->get('text_method_moving_avg'),
            'exponential_smoothing' => $this->language->get('text_method_exponential'),
            'seasonal_decomposition' => $this->language->get('text_method_seasonal'),
            'arima' => $this->language->get('text_method_arima'),
            'neural_network' => $this->language->get('text_method_neural')
        ];

        return $methods[$method] ?? $method;
    }

    private function getQuickStatistics() {
        $this->load->model('crm/sales_forecast');

        return [
            'total_forecasts' => $this->model_crm_sales_forecast->getTotalForecasts(),
            'active_forecasts' => $this->model_crm_sales_forecast->getActiveForecasts(),
            'avg_accuracy' => $this->model_crm_sales_forecast->getAverageAccuracy(),
            'best_method' => $this->model_crm_sales_forecast->getBestMethod(),
            'next_period_prediction' => $this->model_crm_sales_forecast->getNextPeriodPrediction(),
            'variance_trend' => $this->model_crm_sales_forecast->getVarianceTrend()
        ];
    }

    private function getChartsData() {
        $this->load->model('crm/sales_forecast');

        return [
            'accuracy_chart' => $this->model_crm_sales_forecast->getAccuracyChart(),
            'forecast_trend' => $this->model_crm_sales_forecast->getForecastTrendChart(),
            'method_comparison' => $this->model_crm_sales_forecast->getMethodComparisonChart(),
            'variance_chart' => $this->model_crm_sales_forecast->getVarianceChart()
        ];
    }

    private function getPeriods() {
        return [
            'daily' => $this->language->get('text_period_daily'),
            'weekly' => $this->language->get('text_period_weekly'),
            'monthly' => $this->language->get('text_period_monthly'),
            'quarterly' => $this->language->get('text_period_quarterly'),
            'yearly' => $this->language->get('text_period_yearly')
        ];
    }

    private function getForecastTypes() {
        return [
            'revenue' => $this->language->get('text_type_revenue'),
            'units' => $this->language->get('text_type_units'),
            'customers' => $this->language->get('text_type_customers'),
            'orders' => $this->language->get('text_type_orders')
        ];
    }

    private function getForecastMethods() {
        return [
            'linear_regression' => $this->language->get('text_method_linear'),
            'moving_average' => $this->language->get('text_method_moving_avg'),
            'exponential_smoothing' => $this->language->get('text_method_exponential'),
            'seasonal_decomposition' => $this->language->get('text_method_seasonal'),
            'arima' => $this->language->get('text_method_arima'),
            'neural_network' => $this->language->get('text_method_neural')
        ];
    }

    private function getAutoGenerationMethods() {
        return [
            'auto_best' => $this->language->get('text_auto_best'),
            'ensemble' => $this->language->get('text_auto_ensemble'),
            'all_methods' => $this->language->get('text_auto_all')
        ];
    }

    private function getScenarioTypes() {
        return [
            'optimistic' => $this->language->get('text_scenario_optimistic'),
            'realistic' => $this->language->get('text_scenario_realistic'),
            'pessimistic' => $this->language->get('text_scenario_pessimistic'),
            'custom' => $this->language->get('text_scenario_custom')
        ];
    }

    private function getAdjustmentFactors() {
        return [
            'market_growth' => $this->language->get('text_factor_market'),
            'competition' => $this->language->get('text_factor_competition'),
            'seasonality' => $this->language->get('text_factor_seasonality'),
            'economic_conditions' => $this->language->get('text_factor_economic'),
            'marketing_campaigns' => $this->language->get('text_factor_marketing')
        ];
    }

    private function calculateForecastStatistics($forecast, $historical_data) {
        $total_variance = 0;
        $data_points = count($historical_data);

        foreach ($historical_data as $data) {
            if ($data['actual_amount'] > 0) {
                $variance = abs($data['predicted_amount'] - $data['actual_amount']) / $data['actual_amount'];
                $total_variance += $variance;
            }
        }

        $avg_variance = $data_points > 0 ? ($total_variance / $data_points) * 100 : 0;

        return [
            'data_points' => $data_points,
            'avg_variance' => round($avg_variance, 2),
            'reliability_score' => max(0, 100 - $avg_variance),
            'trend_direction' => $this->calculateTrendDirection($historical_data)
        ];
    }

    private function calculateTrendDirection($data) {
        if (count($data) < 2) return 'stable';

        $first_half = array_slice($data, 0, floor(count($data) / 2));
        $second_half = array_slice($data, floor(count($data) / 2));

        $first_avg = array_sum(array_column($first_half, 'actual_amount')) / count($first_half);
        $second_avg = array_sum(array_column($second_half, 'actual_amount')) / count($second_half);

        $change_percentage = (($second_avg - $first_avg) / $first_avg) * 100;

        if ($change_percentage > 5) return 'increasing';
        if ($change_percentage < -5) return 'decreasing';
        return 'stable';
    }

    private function validateForm() {
        if (!$this->user->hasPermission('modify', 'crm/sales_forecast')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        return !$this->error;
    }

    private function validateAutoGenerate() {
        if (!$this->user->hasPermission('modify', 'crm/sales_forecast')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        return !$this->error;
    }

    private function validateScenarios() {
        if (!$this->user->hasPermission('modify', 'crm/sales_forecast')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        return !$this->error;
    }

    /**
     * الحصول على البيانات في الوقت الفعلي
     */
    public function getRealTimeData() {
        $json = array();

        // فحص الصلاحيات
        if (!$this->user->hasPermission('access', 'crm/sales_forecast')) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            $json['success'] = true;
            $json['data'] = array(
                'accuracy' => $this->calculateCurrentAccuracy(),
                'variance' => $this->calculateCurrentVariance(),
                'trend' => $this->identifyCurrentTrend(),
                'comparison' => $this->getForecastVsActualComparison()
            );
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    /**
     * ضبط معاملات الخوارزمية
     */
    public function tuneParameters() {
        $this->load->language('crm/sales_forecast');

        $json = array();

        // فحص الصلاحيات
        if (!$this->user->hasPermission('modify', 'crm/sales_forecast')) {
            $json['error'] = $this->language->get('error_permission');
        }

        $algorithm = isset($this->request->post['algorithm']) ? $this->request->post['algorithm'] : '';
        $parameters = isset($this->request->post['parameters']) ? $this->request->post['parameters'] : array();

        if (!$json && empty($algorithm)) {
            $json['error'] = $this->language->get('error_algorithm_required');
        }

        if (!$json) {
            try {
                // تطبيق المعاملات الجديدة
                $tuning_results = $this->performParameterTuning($algorithm, $parameters);

                // حفظ المعاملات المحسنة
                $this->saveTunedParameters($algorithm, $tuning_results['best_parameters']);

                $json['success'] = true;
                $json['data'] = $tuning_results;

            } catch (Exception $e) {
                $json['error'] = 'حدث خطأ أثناء ضبط المعاملات: ' . $e->getMessage();
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    /**
     * تهيئة خوارزميات التوقع
     */
    private function initializeForecastAlgorithms() {
        $this->forecast_algorithms = array(
            'linear' => array(
                'name' => 'الانحدار الخطي',
                'description' => 'توقع خطي بسيط مناسب للاتجاهات المستقرة',
                'complexity' => 'منخفض',
                'accuracy' => 'متوسط',
                'parameters' => array('slope', 'intercept')
            ),
            'moving_average' => array(
                'name' => 'المتوسط المتحرك',
                'description' => 'متوسط البيانات الأخيرة لتنعيم التقلبات',
                'complexity' => 'منخفض',
                'accuracy' => 'متوسط',
                'parameters' => array('window_size', 'weights')
            ),
            'exponential' => array(
                'name' => 'التنعيم الأسي',
                'description' => 'يعطي وزن أكبر للبيانات الحديثة',
                'complexity' => 'متوسط',
                'accuracy' => 'جيد',
                'parameters' => array('alpha', 'beta', 'gamma')
            ),
            'seasonal' => array(
                'name' => 'التحليل الموسمي',
                'description' => 'يأخذ في الاعتبار الأنماط الموسمية',
                'complexity' => 'متوسط',
                'accuracy' => 'جيد',
                'parameters' => array('seasonal_period', 'trend_component', 'seasonal_component')
            ),
            'arima' => array(
                'name' => 'نموذج ARIMA',
                'description' => 'نموذج متقدم للسلاسل الزمنية',
                'complexity' => 'عالي',
                'accuracy' => 'ممتاز',
                'parameters' => array('p', 'd', 'q', 'seasonal_p', 'seasonal_d', 'seasonal_q')
            ),
            'neural' => array(
                'name' => 'الشبكة العصبية',
                'description' => 'ذكاء اصطناعي لأنماط معقدة',
                'complexity' => 'عالي جداً',
                'accuracy' => 'ممتاز',
                'parameters' => array('hidden_layers', 'neurons', 'learning_rate', 'epochs')
            )
        );
    }

    /**
     * تحميل طرق التوقع
     */
    private function loadForecastMethods() {
        $this->forecast_methods = array(
            'linear' => 'الانحدار الخطي',
            'moving_average' => 'المتوسط المتحرك',
            'exponential' => 'التنعيم الأسي',
            'seasonal' => 'التحليل الموسمي',
            'arima' => 'نموذج ARIMA',
            'neural' => 'الشبكة العصبية'
        );
    }

    /**
     * معالجة الفلاتر
     */
    private function processFilters() {
        $filter_data = array();

        // فلتر الفترة
        if (isset($this->request->get['filter_period'])) {
            $filter_data['filter_period'] = $this->request->get['filter_period'];
        }

        // فلتر نوع التوقع
        if (isset($this->request->get['filter_type'])) {
            $filter_data['filter_type'] = $this->request->get['filter_type'];
        }

        // فلتر الطريقة
        if (isset($this->request->get['filter_method'])) {
            $filter_data['filter_method'] = $this->request->get['filter_method'];
        }

        // فلتر مستوى الأداء
        if (isset($this->request->get['filter_performance'])) {
            $filter_data['filter_performance'] = $this->request->get['filter_performance'];
        }

        // فلتر التاريخ
        if (isset($this->request->get['filter_date_from'])) {
            $filter_data['filter_date_from'] = $this->request->get['filter_date_from'];
        }

        if (isset($this->request->get['filter_date_to'])) {
            $filter_data['filter_date_to'] = $this->request->get['filter_date_to'];
        }

        // الترتيب
        if (isset($this->request->get['sort'])) {
            $filter_data['sort'] = $this->request->get['sort'];
        } else {
            $filter_data['sort'] = 'date_created';
        }

        if (isset($this->request->get['order'])) {
            $filter_data['order'] = $this->request->get['order'];
        } else {
            $filter_data['order'] = 'DESC';
        }

        // التصفح
        if (isset($this->request->get['page'])) {
            $filter_data['page'] = (int)$this->request->get['page'];
        } else {
            $filter_data['page'] = 1;
        }

        $filter_data['limit'] = 20;
        $filter_data['start'] = ($filter_data['page'] - 1) * $filter_data['limit'];

        return $filter_data;
    }

    /**
     * حساب الإحصائيات
     */
    private function calculateStatistics() {
        $statistics = array();

        // إجمالي التوقعات النشطة
        $statistics['total_forecasts'] = $this->model_crm_sales_forecast->getTotalActiveForecasts();

        // متوسط دقة التوقعات
        $statistics['average_accuracy'] = $this->model_crm_sales_forecast->getAverageAccuracy();

        // أفضل طريقة توقع
        $statistics['best_method'] = $this->model_crm_sales_forecast->getBestPerformingMethod();

        // اتجاه الأداء
        $statistics['performance_trend'] = $this->model_crm_sales_forecast->getPerformanceTrend();

        return $statistics;
    }

    /**
     * إعداد بيانات الرسوم البيانية
     */
    private function prepareChartsData() {
        $charts = array();

        // رسم دقة التوقعات
        $charts['accuracy_trend'] = $this->model_crm_sales_forecast->getAccuracyTrendData();

        // رسم مقارنة الطرق
        $charts['method_comparison'] = $this->model_crm_sales_forecast->getMethodComparisonData();

        // رسم التوقع مقابل الفعلي
        $charts['forecast_vs_actual'] = $this->model_crm_sales_forecast->getForecastVsActualData();

        // رسم فترة الثقة
        $charts['confidence_interval'] = $this->model_crm_sales_forecast->getConfidenceIntervalData();

        return $charts;
    }

    /**
     * تنسيق بيانات التوقعات
     */
    private function formatForecastsData($forecasts) {
        $formatted = array();

        foreach ($forecasts as $forecast) {
            $formatted[] = array(
                'forecast_id' => $forecast['forecast_id'],
                'period' => $forecast['period'],
                'forecast_type' => $forecast['forecast_type'],
                'method' => $this->forecast_methods[$forecast['method']] ?? $forecast['method'],
                'predicted_amount' => number_format($forecast['predicted_amount'], 2),
                'actual_amount' => $forecast['actual_amount'] ? number_format($forecast['actual_amount'], 2) : '-',
                'accuracy' => $forecast['accuracy'] ? round($forecast['accuracy'], 2) . '%' : '-',
                'confidence_level' => $forecast['confidence_level'] . '%',
                'variance' => $forecast['variance'] ? round($forecast['variance'], 2) . '%' : '-',
                'performance_score' => $this->calculatePerformanceScore($forecast),
                'performance_class' => $this->getPerformanceClass($forecast),
                'start_date' => date('Y-m-d', strtotime($forecast['start_date'])),
                'end_date' => date('Y-m-d', strtotime($forecast['end_date'])),
                'date_created' => date('Y-m-d H:i', strtotime($forecast['date_created'])),
                'view' => $this->url->link('crm/sales_forecast/view', 'user_token=' . $this->session->data['user_token'] . '&forecast_id=' . $forecast['forecast_id'], true),
                'edit' => $this->url->link('crm/sales_forecast/edit', 'user_token=' . $this->session->data['user_token'] . '&forecast_id=' . $forecast['forecast_id'], true),
                'validate' => $this->url->link('crm/sales_forecast/validate', 'user_token=' . $this->session->data['user_token'] . '&forecast_id=' . $forecast['forecast_id'], true)
            );
        }

        return $formatted;
    }
}
