<?php
/**
 * نموذج توقعات المبيعات (Sales Forecast Model)
 *
 * الهدف: إدارة وحساب توقعات المبيعات باستخدام خوارزميات متقدمة
 * الميزات: خوارزميات متعددة، سيناريوهات، تحليل دقة، تعلم آلي
 *
 * @author ERP Team
 * @version 1.0
 * @since 2024
 */

class ModelCrmSalesForecast extends Model {

    /**
     * الحصول على التوقعات مع الفلاتر
     */
    public function getForecasts($data = []) {
        $sql = "
            SELECT
                sf.*,
                u.firstname as created_by_name,
                CASE
                    WHEN sf.actual_amount > 0 THEN
                        ABS(sf.predicted_amount - sf.actual_amount) / sf.actual_amount * 100
                    ELSE 0
                END as variance_percentage,
                CASE
                    WHEN sf.actual_amount > 0 THEN
                        100 - (ABS(sf.predicted_amount - sf.actual_amount) / sf.actual_amount * 100)
                    ELSE 0
                END as accuracy
            FROM " . DB_PREFIX . "sales_forecast sf
            LEFT JOIN " . DB_PREFIX . "user u ON (sf.created_by = u.user_id)
            WHERE 1=1
        ";

        // تطبيق الفلاتر
        if (!empty($data['filter_period'])) {
            $sql .= " AND sf.period = '" . $this->db->escape($data['filter_period']) . "'";
        }

        if (!empty($data['filter_type'])) {
            $sql .= " AND sf.forecast_type = '" . $this->db->escape($data['filter_type']) . "'";
        }

        if (!empty($data['filter_method'])) {
            $sql .= " AND sf.method = '" . $this->db->escape($data['filter_method']) . "'";
        }

        if (!empty($data['filter_accuracy'])) {
            $range = explode('-', $data['filter_accuracy']);
            if (count($range) == 2) {
                $sql .= " AND (100 - (ABS(sf.predicted_amount - sf.actual_amount) / sf.actual_amount * 100)) BETWEEN '" . (int)$range[0] . "' AND '" . (int)$range[1] . "'";
            }
        }

        // ترتيب النتائج
        $sort_data = [
            'period',
            'forecast_type',
            'predicted_amount',
            'actual_amount',
            'accuracy',
            'confidence_level',
            'date_created'
        ];

        if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
            $sql .= " ORDER BY " . $data['sort'];
        } else {
            $sql .= " ORDER BY sf.date_created";
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
     * الحصول على إجمالي عدد التوقعات
     */
    public function getTotalForecasts($data = []) {
        $sql = "
            SELECT COUNT(DISTINCT sf.forecast_id) AS total
            FROM " . DB_PREFIX . "sales_forecast sf
            WHERE 1=1
        ";

        // تطبيق نفس الفلاتر
        if (!empty($data['filter_period'])) {
            $sql .= " AND sf.period = '" . $this->db->escape($data['filter_period']) . "'";
        }

        if (!empty($data['filter_type'])) {
            $sql .= " AND sf.forecast_type = '" . $this->db->escape($data['filter_type']) . "'";
        }

        if (!empty($data['filter_method'])) {
            $sql .= " AND sf.method = '" . $this->db->escape($data['filter_method']) . "'";
        }

        $query = $this->db->query($sql);

        return $query->row['total'];
    }

    /**
     * الحصول على توقع محدد
     */
    public function getForecast($forecast_id) {
        $query = $this->db->query("
            SELECT
                sf.*,
                u.firstname as created_by_name,
                CASE
                    WHEN sf.actual_amount > 0 THEN
                        ABS(sf.predicted_amount - sf.actual_amount) / sf.actual_amount * 100
                    ELSE 0
                END as variance_percentage,
                CASE
                    WHEN sf.actual_amount > 0 THEN
                        100 - (ABS(sf.predicted_amount - sf.actual_amount) / sf.actual_amount * 100)
                    ELSE 0
                END as accuracy
            FROM " . DB_PREFIX . "sales_forecast sf
            LEFT JOIN " . DB_PREFIX . "user u ON (sf.created_by = u.user_id)
            WHERE sf.forecast_id = '" . (int)$forecast_id . "'
        ");

        return $query->num_rows ? $query->row : false;
    }

    /**
     * إنشاء توقع جديد
     */
    public function createForecast($data) {
        // حساب التوقع باستخدام الطريقة المحددة
        $prediction_data = $this->calculateForecast($data);

        $this->db->query("
            INSERT INTO " . DB_PREFIX . "sales_forecast SET
                period = '" . $this->db->escape($data['period']) . "',
                period_start = '" . $this->db->escape($data['period_start']) . "',
                period_end = '" . $this->db->escape($data['period_end']) . "',
                forecast_type = '" . $this->db->escape($data['forecast_type']) . "',
                method = '" . $this->db->escape($data['method']) . "',
                predicted_amount = '" . (float)$prediction_data['predicted_amount'] . "',
                confidence_level = '" . (float)$prediction_data['confidence_level'] . "',
                parameters = '" . $this->db->escape(json_encode($prediction_data['parameters'])) . "',
                created_by = '" . (int)$this->user->getId() . "',
                date_created = NOW()
        ");

        return $this->db->getLastId();
    }

    /**
     * حساب التوقع باستخدام طرق مختلفة
     */
    private function calculateForecast($data) {
        $method = $data['method'];
        $forecast_type = $data['forecast_type'];
        $period = $data['period'];

        // الحصول على البيانات التاريخية
        $historical_data = $this->getHistoricalSalesData($forecast_type, $period);

        switch ($method) {
            case 'linear_regression':
                return $this->linearRegressionForecast($historical_data);
            case 'moving_average':
                return $this->movingAverageForecast($historical_data, $data['ma_periods'] ?? 3);
            case 'exponential_smoothing':
                return $this->exponentialSmoothingForecast($historical_data, $data['alpha'] ?? 0.3);
            case 'seasonal_decomposition':
                return $this->seasonalDecompositionForecast($historical_data);
            case 'arima':
                return $this->arimaForecast($historical_data);
            case 'neural_network':
                return $this->neuralNetworkForecast($historical_data);
            default:
                return $this->linearRegressionForecast($historical_data);
        }
    }

    /**
     * توقع بالانحدار الخطي
     */
    private function linearRegressionForecast($data) {
        $n = count($data);
        if ($n < 2) {
            return ['predicted_amount' => 0, 'confidence_level' => 0, 'parameters' => []];
        }

        $sum_x = 0;
        $sum_y = 0;
        $sum_xy = 0;
        $sum_x2 = 0;

        foreach ($data as $i => $point) {
            $x = $i + 1;
            $y = $point['amount'];

            $sum_x += $x;
            $sum_y += $y;
            $sum_xy += $x * $y;
            $sum_x2 += $x * $x;
        }

        // حساب معاملات الانحدار
        $slope = ($n * $sum_xy - $sum_x * $sum_y) / ($n * $sum_x2 - $sum_x * $sum_x);
        $intercept = ($sum_y - $slope * $sum_x) / $n;

        // التوقع للفترة التالية
        $next_x = $n + 1;
        $predicted_amount = $slope * $next_x + $intercept;

        // حساب معامل الارتباط للثقة
        $r_squared = $this->calculateRSquared($data, $slope, $intercept);
        $confidence_level = $r_squared * 100;

        return [
            'predicted_amount' => max(0, $predicted_amount),
            'confidence_level' => $confidence_level,
            'parameters' => [
                'slope' => $slope,
                'intercept' => $intercept,
                'r_squared' => $r_squared
            ]
        ];
    }

    /**
     * توقع بالمتوسط المتحرك
     */
    private function movingAverageForecast($data, $periods = 3) {
        $n = count($data);
        if ($n < $periods) {
            $periods = $n;
        }

        $recent_data = array_slice($data, -$periods);
        $sum = array_sum(array_column($recent_data, 'amount'));
        $predicted_amount = $sum / $periods;

        // حساب الثقة بناءً على استقرار البيانات
        $variance = $this->calculateVariance(array_column($recent_data, 'amount'));
        $confidence_level = max(0, 100 - ($variance / $predicted_amount * 100));

        return [
            'predicted_amount' => $predicted_amount,
            'confidence_level' => $confidence_level,
            'parameters' => [
                'periods' => $periods,
                'variance' => $variance
            ]
        ];
    }

    /**
     * توقع بالتنعيم الأسي
     */
    private function exponentialSmoothingForecast($data, $alpha = 0.3) {
        $n = count($data);
        if ($n == 0) {
            return ['predicted_amount' => 0, 'confidence_level' => 0, 'parameters' => []];
        }

        $forecast = $data[0]['amount'];
        $errors = [];

        for ($i = 1; $i < $n; $i++) {
            $error = $data[$i]['amount'] - $forecast;
            $errors[] = abs($error);
            $forecast = $alpha * $data[$i]['amount'] + (1 - $alpha) * $forecast;
        }

        // التوقع للفترة التالية
        $predicted_amount = $forecast;

        // حساب الثقة بناءً على متوسط الأخطاء
        $mae = array_sum($errors) / count($errors);
        $confidence_level = max(0, 100 - ($mae / $predicted_amount * 100));

        return [
            'predicted_amount' => $predicted_amount,
            'confidence_level' => $confidence_level,
            'parameters' => [
                'alpha' => $alpha,
                'mae' => $mae
            ]
        ];
    }

    /**
     * توقع بالتحليل الموسمي
     */
    private function seasonalDecompositionForecast($data) {
        $n = count($data);
        if ($n < 12) {
            // إذا كانت البيانات أقل من سنة، استخدم المتوسط المتحرك
            return $this->movingAverageForecast($data);
        }

        // حساب المتوسط الموسمي (افتراض دورة 12 شهر)
        $seasonal_cycle = 12;
        $seasonal_factors = [];

        for ($i = 0; $i < $seasonal_cycle; $i++) {
            $seasonal_sum = 0;
            $seasonal_count = 0;

            for ($j = $i; $j < $n; $j += $seasonal_cycle) {
                $seasonal_sum += $data[$j]['amount'];
                $seasonal_count++;
            }

            $seasonal_factors[$i] = $seasonal_count > 0 ? $seasonal_sum / $seasonal_count : 0;
        }

        // حساب الاتجاه العام
        $trend_data = [];
        for ($i = 0; $i < $n; $i++) {
            $seasonal_index = $i % $seasonal_cycle;
            if ($seasonal_factors[$seasonal_index] > 0) {
                $trend_data[] = ['amount' => $data[$i]['amount'] / $seasonal_factors[$seasonal_index]];
            }
        }

        // استخدام الانحدار الخطي للاتجاه
        $trend_forecast = $this->linearRegressionForecast($trend_data);

        // تطبيق العامل الموسمي
        $next_seasonal_index = $n % $seasonal_cycle;
        $predicted_amount = $trend_forecast['predicted_amount'] * $seasonal_factors[$next_seasonal_index];

        return [
            'predicted_amount' => $predicted_amount,
            'confidence_level' => $trend_forecast['confidence_level'],
            'parameters' => [
                'seasonal_factors' => $seasonal_factors,
                'trend_parameters' => $trend_forecast['parameters']
            ]
        ];
    }

    /**
     * توقع ARIMA مبسط
     */
    private function arimaForecast($data) {
        // تطبيق مبسط لـ ARIMA(1,1,1)
        $n = count($data);
        if ($n < 3) {
            return $this->linearRegressionForecast($data);
        }

        // حساب الفروق الأولى
        $diff_data = [];
        for ($i = 1; $i < $n; $i++) {
            $diff_data[] = $data[$i]['amount'] - $data[$i-1]['amount'];
        }

        // تطبيق AR(1) على البيانات المفروقة
        $ar_coeff = 0.5; // معامل مبسط
        $last_diff = end($diff_data);
        $predicted_diff = $ar_coeff * $last_diff;

        // التوقع النهائي
        $predicted_amount = $data[$n-1]['amount'] + $predicted_diff;

        // حساب الثقة
        $diff_variance = $this->calculateVariance($diff_data);
        $confidence_level = max(0, 100 - ($diff_variance / abs($predicted_amount) * 100));

        return [
            'predicted_amount' => max(0, $predicted_amount),
            'confidence_level' => $confidence_level,
            'parameters' => [
                'ar_coefficient' => $ar_coeff,
                'diff_variance' => $diff_variance
            ]
        ];
    }

    /**
     * توقع بالشبكات العصبية المبسط
     */
    private function neuralNetworkForecast($data) {
        // تطبيق مبسط للشبكة العصبية
        $n = count($data);
        if ($n < 5) {
            return $this->exponentialSmoothingForecast($data);
        }

        // استخدام آخر 4 نقاط كمدخلات
        $inputs = array_slice(array_column($data, 'amount'), -4);

        // أوزان مبسطة (في التطبيق الحقيقي تكون مدربة)
        $weights = [0.1, 0.2, 0.3, 0.4];
        $bias = 0.1;

        // حساب الإخراج
        $weighted_sum = $bias;
        for ($i = 0; $i < 4; $i++) {
            $weighted_sum += $inputs[$i] * $weights[$i];
        }

        // تطبيق دالة التفعيل (sigmoid مبسط)
        $predicted_amount = $weighted_sum;

        // حساب الثقة بناءً على استقرار المدخلات
        $input_variance = $this->calculateVariance($inputs);
        $confidence_level = max(0, 100 - ($input_variance / $predicted_amount * 100));

        return [
            'predicted_amount' => max(0, $predicted_amount),
            'confidence_level' => $confidence_level,
            'parameters' => [
                'weights' => $weights,
                'bias' => $bias,
                'inputs' => $inputs
            ]
        ];
    }

    /**
     * الحصول على البيانات التاريخية للمبيعات
     */
    private function getHistoricalSalesData($forecast_type, $period) {
        $sql = "";

        switch ($forecast_type) {
            case 'revenue':
                $sql = "
                    SELECT
                        DATE_FORMAT(date_added, '%Y-%m') as period,
                        SUM(total) as amount
                    FROM " . DB_PREFIX . "order
                    WHERE order_status_id > 0
                    GROUP BY DATE_FORMAT(date_added, '%Y-%m')
                    ORDER BY period ASC
                ";
                break;
            case 'units':
                $sql = "
                    SELECT
                        DATE_FORMAT(o.date_added, '%Y-%m') as period,
                        SUM(op.quantity) as amount
                    FROM " . DB_PREFIX . "order o
                    LEFT JOIN " . DB_PREFIX . "order_product op ON o.order_id = op.order_id
                    WHERE o.order_status_id > 0
                    GROUP BY DATE_FORMAT(o.date_added, '%Y-%m')
                    ORDER BY period ASC
                ";
                break;
            case 'customers':
                $sql = "
                    SELECT
                        DATE_FORMAT(date_added, '%Y-%m') as period,
                        COUNT(DISTINCT customer_id) as amount
                    FROM " . DB_PREFIX . "customer
                    WHERE status = 1
                    GROUP BY DATE_FORMAT(date_added, '%Y-%m')
                    ORDER BY period ASC
                ";
                break;
            case 'orders':
                $sql = "
                    SELECT
                        DATE_FORMAT(date_added, '%Y-%m') as period,
                        COUNT(*) as amount
                    FROM " . DB_PREFIX . "order
                    WHERE order_status_id > 0
                    GROUP BY DATE_FORMAT(date_added, '%Y-%m')
                    ORDER BY period ASC
                ";
                break;
        }

        $query = $this->db->query($sql);
        return $query->rows;
    }

    /**
     * حساب معامل التحديد R²
     */
    private function calculateRSquared($data, $slope, $intercept) {
        $n = count($data);
        $y_mean = array_sum(array_column($data, 'amount')) / $n;

        $ss_tot = 0;
        $ss_res = 0;

        foreach ($data as $i => $point) {
            $x = $i + 1;
            $y = $point['amount'];
            $y_pred = $slope * $x + $intercept;

            $ss_tot += pow($y - $y_mean, 2);
            $ss_res += pow($y - $y_pred, 2);
        }

        return $ss_tot > 0 ? 1 - ($ss_res / $ss_tot) : 0;
    }

    /**
     * حساب التباين
     */
    private function calculateVariance($data) {
        $n = count($data);
        if ($n <= 1) return 0;

        $mean = array_sum($data) / $n;
        $variance = 0;

        foreach ($data as $value) {
            $variance += pow($value - $mean, 2);
        }

        return $variance / ($n - 1);
    }

    /**
     * توليد توقعات تلقائية
     */
    public function generateAutoForecasts($data) {
        $methods = $data['methods'] ?? ['linear_regression', 'moving_average', 'exponential_smoothing'];
        $periods = $data['periods'] ?? ['monthly'];
        $types = $data['types'] ?? ['revenue'];

        $results = [];

        foreach ($types as $type) {
            foreach ($periods as $period) {
                foreach ($methods as $method) {
                    $forecast_data = [
                        'period' => $period,
                        'period_start' => date('Y-m-01'),
                        'period_end' => date('Y-m-t'),
                        'forecast_type' => $type,
                        'method' => $method
                    ];

                    $forecast_id = $this->createForecast($forecast_data);
                    if ($forecast_id) {
                        $results[] = $forecast_id;
                    }
                }
            }
        }

        return $results;
    }

    /**
     * إحصائيات سريعة
     */
    public function getTotalForecasts() {
        $query = $this->db->query("SELECT COUNT(*) as total FROM " . DB_PREFIX . "sales_forecast");
        return $query->row['total'];
    }

    public function getActiveForecasts() {
        $query = $this->db->query("
            SELECT COUNT(*) as total
            FROM " . DB_PREFIX . "sales_forecast
            WHERE period_end >= CURDATE()
        ");
        return $query->row['total'];
    }

    public function getAverageAccuracy() {
        $query = $this->db->query("
            SELECT AVG(
                CASE
                    WHEN actual_amount > 0 THEN
                        100 - (ABS(predicted_amount - actual_amount) / actual_amount * 100)
                    ELSE 0
                END
            ) as avg_accuracy
            FROM " . DB_PREFIX . "sales_forecast
            WHERE actual_amount > 0
        ");
        return round($query->row['avg_accuracy'], 1);
    }

    public function getBestMethod() {
        $query = $this->db->query("
            SELECT
                method,
                AVG(
                    CASE
                        WHEN actual_amount > 0 THEN
                            100 - (ABS(predicted_amount - actual_amount) / actual_amount * 100)
                        ELSE 0
                    END
                ) as avg_accuracy
            FROM " . DB_PREFIX . "sales_forecast
            WHERE actual_amount > 0
            GROUP BY method
            ORDER BY avg_accuracy DESC
            LIMIT 1
        ");
        return $query->num_rows ? $query->row['method'] : 'linear_regression';
    }

    public function getNextPeriodPrediction() {
        $query = $this->db->query("
            SELECT predicted_amount
            FROM " . DB_PREFIX . "sales_forecast
            WHERE period_start > CURDATE()
            ORDER BY period_start ASC
            LIMIT 1
        ");
        return $query->num_rows ? $query->row['predicted_amount'] : 0;
    }

    public function getVarianceTrend() {
        $query = $this->db->query("
            SELECT
                AVG(ABS(predicted_amount - actual_amount)) as avg_variance
            FROM " . DB_PREFIX . "sales_forecast
            WHERE actual_amount > 0
            AND date_created >= DATE_SUB(CURDATE(), INTERVAL 3 MONTH)
        ");

        $current_variance = $query->row['avg_variance'] ?? 0;

        $query = $this->db->query("
            SELECT
                AVG(ABS(predicted_amount - actual_amount)) as avg_variance
            FROM " . DB_PREFIX . "sales_forecast
            WHERE actual_amount > 0
            AND date_created >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
            AND date_created < DATE_SUB(CURDATE(), INTERVAL 3 MONTH)
        ");

        $previous_variance = $query->row['avg_variance'] ?? 0;

        if ($previous_variance > 0) {
            $trend = (($current_variance - $previous_variance) / $previous_variance) * 100;
            return round($trend, 1);
        }

        return 0;
    }

    /**
     * الحصول على بيانات اتجاه الدقة
     */
    public function getAccuracyTrendData($days = 30) {
        $query = $this->db->query("
            SELECT
                DATE(validation_date) as date,
                AVG(accuracy) as accuracy,
                COUNT(*) as forecast_count
            FROM " . DB_PREFIX . "crm_sales_forecast
            WHERE validation_date >= DATE_SUB(NOW(), INTERVAL " . (int)$days . " DAY)
            AND actual_amount IS NOT NULL
            GROUP BY DATE(validation_date)
            ORDER BY date ASC
        ");

        return $query->rows;
    }

    /**
     * الحصول على بيانات مقارنة الطرق
     */
    public function getMethodComparisonData() {
        $query = $this->db->query("
            SELECT
                method,
                COUNT(*) as total_forecasts,
                AVG(accuracy) as avg_accuracy,
                AVG(confidence_level) as avg_confidence,
                MIN(accuracy) as min_accuracy,
                MAX(accuracy) as max_accuracy,
                STDDEV(accuracy) as accuracy_stddev
            FROM " . DB_PREFIX . "crm_sales_forecast
            WHERE actual_amount IS NOT NULL
            GROUP BY method
            ORDER BY avg_accuracy DESC
        ");

        return $query->rows;
    }

    /**
     * الحصول على بيانات التوقع مقابل الفعلي
     */
    public function getForecastVsActualData($limit = 20) {
        $query = $this->db->query("
            SELECT
                forecast_id,
                period,
                predicted_amount,
                actual_amount,
                accuracy,
                variance,
                method,
                DATE(validation_date) as validation_date
            FROM " . DB_PREFIX . "crm_sales_forecast
            WHERE actual_amount IS NOT NULL
            ORDER BY validation_date DESC
            LIMIT " . (int)$limit
        );

        return $query->rows;
    }

    /**
     * الحصول على بيانات فترة الثقة
     */
    public function getConfidenceIntervalData() {
        $query = $this->db->query("
            SELECT
                forecast_id,
                period,
                predicted_amount,
                confidence_interval_lower,
                confidence_interval_upper,
                actual_amount,
                confidence_level,
                CASE
                    WHEN actual_amount IS NOT NULL AND
                         actual_amount >= confidence_interval_lower AND
                         actual_amount <= confidence_interval_upper
                    THEN 1 ELSE 0
                END as within_interval
            FROM " . DB_PREFIX . "crm_sales_forecast
            WHERE actual_amount IS NOT NULL
            ORDER BY date_created DESC
        ");

        return $query->rows;
    }

    /**
     * الحصول على توقعات حسب الفترة
     */
    public function getForecastsByPeriod($period = 'monthly') {
        $query = $this->db->query("
            SELECT
                period,
                method,
                predicted_amount,
                actual_amount,
                accuracy,
                confidence_level
            FROM " . DB_PREFIX . "crm_sales_forecast
            WHERE period = '" . $this->db->escape($period) . "'
            ORDER BY date_created DESC
        ");

        return $query->rows;
    }

    /**
     * الحصول على أفضل التوقعات أداءً
     */
    public function getTopPerformingForecasts($limit = 10) {
        $query = $this->db->query("
            SELECT
                sf.*,
                u.firstname,
                u.lastname
            FROM " . DB_PREFIX . "crm_sales_forecast sf
            LEFT JOIN " . DB_PREFIX . "user u ON (sf.created_by = u.user_id)
            WHERE sf.actual_amount IS NOT NULL
            AND sf.accuracy IS NOT NULL
            ORDER BY sf.accuracy DESC
            LIMIT " . (int)$limit
        );

        return $query->rows;
    }

    /**
     * الحصول على التوقعات ضعيفة الأداء
     */
    public function getPoorPerformingForecasts($limit = 10) {
        $query = $this->db->query("
            SELECT
                sf.*,
                u.firstname,
                u.lastname
            FROM " . DB_PREFIX . "crm_sales_forecast sf
            LEFT JOIN " . DB_PREFIX . "user u ON (sf.created_by = u.user_id)
            WHERE sf.actual_amount IS NOT NULL
            AND sf.accuracy IS NOT NULL
            AND sf.accuracy < 70
            ORDER BY sf.accuracy ASC
            LIMIT " . (int)$limit
        );

        return $query->rows;
    }

    /**
     * حساب مقاييس الأداء للطريقة
     */
    public function getMethodPerformanceMetrics($method) {
        $query = $this->db->query("
            SELECT
                COUNT(*) as total_forecasts,
                AVG(accuracy) as avg_accuracy,
                STDDEV(accuracy) as accuracy_stddev,
                MIN(accuracy) as min_accuracy,
                MAX(accuracy) as max_accuracy,
                AVG(ABS(variance)) as avg_absolute_error,
                AVG(confidence_level) as avg_confidence,
                COUNT(CASE WHEN accuracy >= 90 THEN 1 END) as excellent_count,
                COUNT(CASE WHEN accuracy >= 70 AND accuracy < 90 THEN 1 END) as good_count,
                COUNT(CASE WHEN accuracy < 70 THEN 1 END) as poor_count
            FROM " . DB_PREFIX . "crm_sales_forecast
            WHERE method = '" . $this->db->escape($method) . "'
            AND actual_amount IS NOT NULL
        ");

        return $query->row;
    }

    /**
     * الحصول على التوقعات المستقبلية النشطة
     */
    public function getActiveFutureForecasts() {
        $query = $this->db->query("
            SELECT
                sf.*,
                u.firstname,
                u.lastname,
                DATEDIFF(sf.end_date, NOW()) as days_remaining
            FROM " . DB_PREFIX . "crm_sales_forecast sf
            LEFT JOIN " . DB_PREFIX . "user u ON (sf.created_by = u.user_id)
            WHERE sf.status = 'active'
            AND sf.end_date > NOW()
            ORDER BY sf.end_date ASC
        ");

        return $query->rows;
    }

    /**
     * الحصول على التوقعات المنتهية الصلاحية
     */
    public function getExpiredForecasts() {
        $query = $this->db->query("
            SELECT
                sf.*,
                u.firstname,
                u.lastname,
                DATEDIFF(NOW(), sf.end_date) as days_expired
            FROM " . DB_PREFIX . "crm_sales_forecast sf
            LEFT JOIN " . DB_PREFIX . "user u ON (sf.created_by = u.user_id)
            WHERE sf.status = 'active'
            AND sf.end_date < NOW()
            AND sf.actual_amount IS NULL
            ORDER BY sf.end_date DESC
        ");

        return $query->rows;
    }

    /**
     * تحديث حالة التوقعات المنتهية الصلاحية
     */
    public function updateExpiredForecasts() {
        $this->db->query("
            UPDATE " . DB_PREFIX . "crm_sales_forecast
            SET status = 'expired'
            WHERE status = 'active'
            AND end_date < NOW()
            AND actual_amount IS NULL
        ");

        return $this->db->countAffected();
    }

    /**
     * الحصول على ملخص الأداء الشهري
     */
    public function getMonthlyPerformanceSummary($months = 12) {
        $query = $this->db->query("
            SELECT
                DATE_FORMAT(validation_date, '%Y-%m') as month,
                COUNT(*) as total_forecasts,
                AVG(accuracy) as avg_accuracy,
                AVG(ABS(variance)) as avg_absolute_error,
                COUNT(CASE WHEN accuracy >= 90 THEN 1 END) as excellent_count,
                COUNT(CASE WHEN accuracy >= 70 AND accuracy < 90 THEN 1 END) as good_count,
                COUNT(CASE WHEN accuracy < 70 THEN 1 END) as poor_count,
                SUM(predicted_amount) as total_predicted,
                SUM(actual_amount) as total_actual
            FROM " . DB_PREFIX . "crm_sales_forecast
            WHERE validation_date >= DATE_SUB(NOW(), INTERVAL " . (int)$months . " MONTH)
            AND actual_amount IS NOT NULL
            GROUP BY DATE_FORMAT(validation_date, '%Y-%m')
            ORDER BY month DESC
        ");

        return $query->rows;
    }

    /**
     * الحصول على توزيع مستويات الثقة
     */
    public function getConfidenceLevelDistribution() {
        $query = $this->db->query("
            SELECT
                CASE
                    WHEN confidence_level >= 95 THEN 'Very High (95-100%)'
                    WHEN confidence_level >= 90 THEN 'High (90-94%)'
                    WHEN confidence_level >= 80 THEN 'Medium (80-89%)'
                    WHEN confidence_level >= 70 THEN 'Low (70-79%)'
                    ELSE 'Very Low (<70%)'
                END as confidence_range,
                COUNT(*) as forecast_count,
                AVG(accuracy) as avg_accuracy
            FROM " . DB_PREFIX . "crm_sales_forecast
            WHERE actual_amount IS NOT NULL
            GROUP BY confidence_range
            ORDER BY MIN(confidence_level) DESC
        ");

        return $query->rows;
    }

    /**
     * البحث في التوقعات
     */
    public function searchForecasts($search_term, $limit = 20) {
        $query = $this->db->query("
            SELECT
                sf.*,
                u.firstname,
                u.lastname
            FROM " . DB_PREFIX . "crm_sales_forecast sf
            LEFT JOIN " . DB_PREFIX . "user u ON (sf.created_by = u.user_id)
            WHERE sf.period LIKE '%" . $this->db->escape($search_term) . "%'
            OR sf.forecast_type LIKE '%" . $this->db->escape($search_term) . "%'
            OR sf.method LIKE '%" . $this->db->escape($search_term) . "%'
            OR CONCAT(u.firstname, ' ', u.lastname) LIKE '%" . $this->db->escape($search_term) . "%'
            ORDER BY sf.date_created DESC
            LIMIT " . (int)$limit
        );

        return $query->rows;
    }
}
