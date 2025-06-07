<?php
/**
 * مركز إدارة الذكاء الاصطناعي
 * 
 * يوفر إدارة شاملة لجميع خدمات وتطبيقات الذكاء الاصطناعي مع:
 * - إدارة النماذج والخوارزميات
 * - تكامل مع APIs خارجية (OpenAI, Google AI, etc.)
 * - تدريب النماذج المخصصة
 * - مراقبة الأداء والاستخدام
 * - أتمتة العمليات الذكية
 * - تحليل البيانات المتقدم
 * 
 * @author ERP Team
 * @version 1.0
 * @since 2024
 */

class ModelAiAiCenterManagement extends Model {
    
    /**
     * إضافة نموذج ذكاء اصطناعي جديد
     */
    public function addAiModel($data) {
        $this->db->query("
            INSERT INTO cod_ai_model SET 
            name = '" . $this->db->escape($data['name']) . "',
            description = '" . $this->db->escape($data['description']) . "',
            model_type = '" . $this->db->escape($data['model_type']) . "',
            provider = '" . $this->db->escape($data['provider']) . "',
            model_version = '" . $this->db->escape($data['model_version']) . "',
            api_endpoint = '" . $this->db->escape($data['api_endpoint']) . "',
            api_key = '" . $this->db->escape($this->encrypt($data['api_key'])) . "',
            configuration = '" . $this->db->escape(json_encode($data['configuration'])) . "',
            input_schema = '" . $this->db->escape(json_encode($data['input_schema'])) . "',
            output_schema = '" . $this->db->escape(json_encode($data['output_schema'])) . "',
            cost_per_request = '" . (float)($data['cost_per_request'] ?? 0) . "',
            max_requests_per_hour = '" . (int)($data['max_requests_per_hour'] ?? 1000) . "',
            timeout_seconds = '" . (int)($data['timeout_seconds'] ?? 30) . "',
            status = '" . $this->db->escape($data['status']) . "',
            department_access = '" . $this->db->escape(json_encode($data['department_access'] ?? [])) . "',
            user_access = '" . $this->db->escape(json_encode($data['user_access'] ?? [])) . "',
            created_by = '" . (int)$this->user->getId() . "',
            created_at = NOW()
        ");
        
        $model_id = $this->db->getLastId();
        
        // إضافة قدرات النموذج
        if (!empty($data['capabilities'])) {
            foreach ($data['capabilities'] as $capability) {
                $this->addModelCapability($model_id, $capability);
            }
        }
        
        // تسجيل النشاط
        $this->logAiActivity($model_id, 'model_created', 'تم إنشاء نموذج ذكاء اصطناعي جديد');
        
        return $model_id;
    }
    
    /**
     * تنفيذ طلب ذكاء اصطناعي
     */
    public function executeAiRequest($model_id, $input_data, $context = []) {
        $model = $this->getAiModel($model_id);
        
        if (!$model || $model['status'] != 'active') {
            throw new Exception('النموذج غير متاح أو غير نشط');
        }
        
        // التحقق من الصلاحيات
        if (!$this->hasModelAccess($model_id)) {
            throw new Exception('ليس لديك صلاحية لاستخدام هذا النموذج');
        }
        
        // التحقق من حدود الاستخدام
        if (!$this->checkUsageLimits($model_id)) {
            throw new Exception('تم تجاوز حد الاستخدام المسموح');
        }
        
        // إنشاء سجل الطلب
        $request_id = $this->createAiRequest($model_id, $input_data, $context);
        
        try {
            // تنفيذ الطلب حسب نوع المزود
            $result = $this->executeByProvider($model, $input_data, $context);
            
            // تحديث سجل الطلب بالنتيجة
            $this->updateAiRequest($request_id, 'completed', $result);
            
            // تحديث إحصائيات الاستخدام
            $this->updateUsageStatistics($model_id, $result);
            
            return $result;
            
        } catch (Exception $e) {
            // تحديث سجل الطلب بالخطأ
            $this->updateAiRequest($request_id, 'failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }
    
    /**
     * تنفيذ الطلب حسب المزود
     */
    private function executeByProvider($model, $input_data, $context) {
        switch ($model['provider']) {
            case 'openai':
                return $this->executeOpenAiRequest($model, $input_data, $context);
                
            case 'google':
                return $this->executeGoogleAiRequest($model, $input_data, $context);
                
            case 'azure':
                return $this->executeAzureAiRequest($model, $input_data, $context);
                
            case 'custom':
                return $this->executeCustomModelRequest($model, $input_data, $context);
                
            case 'local':
                return $this->executeLocalModelRequest($model, $input_data, $context);
                
            default:
                throw new Exception('مزود الذكاء الاصطناعي غير مدعوم: ' . $model['provider']);
        }
    }
    
    /**
     * تنفيذ طلب OpenAI
     */
    private function executeOpenAiRequest($model, $input_data, $context) {
        $api_key = $this->decrypt($model['api_key']);
        $configuration = json_decode($model['configuration'], true);
        
        $headers = [
            'Authorization: Bearer ' . $api_key,
            'Content-Type: application/json'
        ];
        
        $payload = [
            'model' => $configuration['model_name'] ?? 'gpt-3.5-turbo',
            'messages' => $this->formatOpenAiMessages($input_data, $context),
            'temperature' => $configuration['temperature'] ?? 0.7,
            'max_tokens' => $configuration['max_tokens'] ?? 1000,
            'top_p' => $configuration['top_p'] ?? 1,
            'frequency_penalty' => $configuration['frequency_penalty'] ?? 0,
            'presence_penalty' => $configuration['presence_penalty'] ?? 0
        ];
        
        $response = $this->makeHttpRequest(
            $model['api_endpoint'] ?: 'https://api.openai.com/v1/chat/completions',
            'POST',
            $headers,
            json_encode($payload),
            $model['timeout_seconds']
        );
        
        return $this->parseOpenAiResponse($response);
    }
    
    /**
     * تنفيذ طلب Google AI
     */
    private function executeGoogleAiRequest($model, $input_data, $context) {
        $api_key = $this->decrypt($model['api_key']);
        $configuration = json_decode($model['configuration'], true);
        
        $headers = [
            'Authorization: Bearer ' . $api_key,
            'Content-Type: application/json'
        ];
        
        $payload = [
            'instances' => [$this->formatGoogleAiInput($input_data, $context)],
            'parameters' => $configuration['parameters'] ?? []
        ];
        
        $response = $this->makeHttpRequest(
            $model['api_endpoint'],
            'POST',
            $headers,
            json_encode($payload),
            $model['timeout_seconds']
        );
        
        return $this->parseGoogleAiResponse($response);
    }
    
    /**
     * تحليل البيانات باستخدام الذكاء الاصطناعي
     */
    public function analyzeData($analysis_type, $data, $options = []) {
        switch ($analysis_type) {
            case 'sales_forecast':
                return $this->analyzeSalesForecast($data, $options);
                
            case 'inventory_optimization':
                return $this->analyzeInventoryOptimization($data, $options);
                
            case 'customer_behavior':
                return $this->analyzeCustomerBehavior($data, $options);
                
            case 'fraud_detection':
                return $this->analyzeFraudDetection($data, $options);
                
            case 'demand_prediction':
                return $this->analyzeDemandPrediction($data, $options);
                
            default:
                throw new Exception('نوع التحليل غير مدعوم: ' . $analysis_type);
        }
    }
    
    /**
     * تحليل توقعات المبيعات
     */
    private function analyzeSalesForecast($data, $options) {
        // الحصول على نموذج التنبؤ بالمبيعات
        $model = $this->getModelByType('sales_forecast');
        
        if (!$model) {
            throw new Exception('نموذج التنبؤ بالمبيعات غير متاح');
        }
        
        // تحضير البيانات
        $prepared_data = $this->prepareSalesData($data, $options);
        
        // تنفيذ التحليل
        $result = $this->executeAiRequest($model['model_id'], $prepared_data, [
            'analysis_type' => 'sales_forecast',
            'period' => $options['period'] ?? 'monthly',
            'horizon' => $options['horizon'] ?? 12
        ]);
        
        // معالجة النتائج
        return $this->processSalesForecastResult($result, $options);
    }
    
    /**
     * تحليل تحسين المخزون
     */
    private function analyzeInventoryOptimization($data, $options) {
        $model = $this->getModelByType('inventory_optimization');
        
        if (!$model) {
            throw new Exception('نموذج تحسين المخزون غير متاح');
        }
        
        $prepared_data = $this->prepareInventoryData($data, $options);
        
        $result = $this->executeAiRequest($model['model_id'], $prepared_data, [
            'analysis_type' => 'inventory_optimization',
            'optimization_goal' => $options['goal'] ?? 'minimize_cost',
            'constraints' => $options['constraints'] ?? []
        ]);
        
        return $this->processInventoryOptimizationResult($result, $options);
    }
    
    /**
     * إنشاء تقرير ذكي
     */
    public function generateIntelligentReport($report_type, $data, $options = []) {
        $model = $this->getModelByType('report_generation');
        
        if (!$model) {
            throw new Exception('نموذج إنشاء التقارير غير متاح');
        }
        
        $context = [
            'report_type' => $report_type,
            'language' => $options['language'] ?? 'ar',
            'format' => $options['format'] ?? 'detailed',
            'include_charts' => $options['include_charts'] ?? true,
            'include_recommendations' => $options['include_recommendations'] ?? true
        ];
        
        $result = $this->executeAiRequest($model['model_id'], $data, $context);
        
        // إنشاء التقرير النهائي
        return $this->generateFinalReport($result, $options);
    }
    
    /**
     * اكتشاف الشذوذ والاحتيال
     */
    public function detectAnomalies($data_type, $data, $sensitivity = 'medium') {
        $model = $this->getModelByType('anomaly_detection');
        
        if (!$model) {
            throw new Exception('نموذج اكتشاف الشذوذ غير متاح');
        }
        
        $prepared_data = $this->prepareAnomalyData($data, $data_type);
        
        $result = $this->executeAiRequest($model['model_id'], $prepared_data, [
            'data_type' => $data_type,
            'sensitivity' => $sensitivity,
            'threshold' => $this->getSensitivityThreshold($sensitivity)
        ]);
        
        return $this->processAnomalyResult($result, $data_type);
    }
    
    /**
     * تحسين العمليات التجارية
     */
    public function optimizeBusinessProcess($process_type, $current_data, $goals = []) {
        $model = $this->getModelByType('process_optimization');
        
        if (!$model) {
            throw new Exception('نموذج تحسين العمليات غير متاح');
        }
        
        $analysis_data = [
            'current_process' => $current_data,
            'performance_metrics' => $this->getProcessMetrics($process_type),
            'constraints' => $this->getProcessConstraints($process_type),
            'goals' => $goals
        ];
        
        $result = $this->executeAiRequest($model['model_id'], $analysis_data, [
            'process_type' => $process_type,
            'optimization_approach' => 'comprehensive'
        ]);
        
        return $this->processOptimizationResult($result, $process_type);
    }
    
    /**
     * الحصول على نموذج حسب النوع
     */
    private function getModelByType($model_type) {
        $query = $this->db->query("
            SELECT * FROM cod_ai_model 
            WHERE model_type = '" . $this->db->escape($model_type) . "'
            AND status = 'active'
            ORDER BY created_at DESC
            LIMIT 1
        ");
        
        return $query->num_rows ? $query->row : false;
    }
    
    /**
     * التحقق من صلاحية الوصول للنموذج
     */
    private function hasModelAccess($model_id) {
        $model = $this->getAiModel($model_id);
        
        if (!$model) {
            return false;
        }
        
        $user_id = $this->user->getId();
        $user_department = $this->getUserDepartment($user_id);
        
        // التحقق من صلاحية المستخدم
        $user_access = json_decode($model['user_access'], true);
        if (!empty($user_access) && !in_array($user_id, $user_access)) {
            return false;
        }
        
        // التحقق من صلاحية القسم
        $department_access = json_decode($model['department_access'], true);
        if (!empty($department_access) && !in_array($user_department, $department_access)) {
            return false;
        }
        
        return true;
    }
    
    /**
     * التحقق من حدود الاستخدام
     */
    private function checkUsageLimits($model_id) {
        $model = $this->getAiModel($model_id);
        
        if (!$model || $model['max_requests_per_hour'] <= 0) {
            return true;
        }
        
        $current_hour_usage = $this->db->query("
            SELECT COUNT(*) as count FROM cod_ai_request 
            WHERE model_id = '" . (int)$model_id . "'
            AND created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
            AND status != 'failed'
        ");
        
        return $current_hour_usage->row['count'] < $model['max_requests_per_hour'];
    }
    
    /**
     * إنشاء سجل طلب ذكاء اصطناعي
     */
    private function createAiRequest($model_id, $input_data, $context) {
        $this->db->query("
            INSERT INTO cod_ai_request SET 
            model_id = '" . (int)$model_id . "',
            user_id = '" . (int)$this->user->getId() . "',
            input_data = '" . $this->db->escape(json_encode($input_data)) . "',
            context = '" . $this->db->escape(json_encode($context)) . "',
            status = 'processing',
            created_at = NOW()
        ");
        
        return $this->db->getLastId();
    }
    
    /**
     * تحديث سجل الطلب
     */
    private function updateAiRequest($request_id, $status, $result = null) {
        $sql = "UPDATE cod_ai_request SET 
                status = '" . $this->db->escape($status) . "',
                completed_at = NOW()";
        
        if ($result !== null) {
            $sql .= ", output_data = '" . $this->db->escape(json_encode($result)) . "'";
            
            if (isset($result['tokens_used'])) {
                $sql .= ", tokens_used = '" . (int)$result['tokens_used'] . "'";
            }
            
            if (isset($result['cost'])) {
                $sql .= ", cost = '" . (float)$result['cost'] . "'";
            }
        }
        
        $sql .= " WHERE request_id = '" . (int)$request_id . "'";
        
        $this->db->query($sql);
    }
    
    /**
     * تشفير البيانات الحساسة
     */
    private function encrypt($data) {
        return base64_encode(openssl_encrypt($data, 'AES-256-CBC', $this->config->get('config_encryption_key'), 0, substr(md5($this->config->get('config_encryption_key')), 0, 16)));
    }
    
    /**
     * فك تشفير البيانات
     */
    private function decrypt($encrypted_data) {
        return openssl_decrypt(base64_decode($encrypted_data), 'AES-256-CBC', $this->config->get('config_encryption_key'), 0, substr(md5($this->config->get('config_encryption_key')), 0, 16));
    }
    
    /**
     * تسجيل نشاط الذكاء الاصطناعي
     */
    private function logAiActivity($model_id, $action, $description) {
        $this->db->query("
            INSERT INTO cod_ai_activity_log SET 
            model_id = '" . (int)$model_id . "',
            user_id = '" . (int)$this->user->getId() . "',
            action = '" . $this->db->escape($action) . "',
            description = '" . $this->db->escape($description) . "',
            created_at = NOW()
        ");
    }
    
    /**
     * الحصول على نموذج ذكاء اصطناعي
     */
    public function getAiModel($model_id) {
        $query = $this->db->query("
            SELECT * FROM cod_ai_model 
            WHERE model_id = '" . (int)$model_id . "'
        ");
        
        return $query->num_rows ? $query->row : false;
    }
    
    /**
     * الحصول على قائمة النماذج
     */
    public function getAiModels($filter_data = []) {
        $sql = "SELECT am.*, 
                COUNT(DISTINCT ar.request_id) as total_requests,
                AVG(ar.cost) as avg_cost,
                SUM(ar.tokens_used) as total_tokens
                FROM cod_ai_model am
                LEFT JOIN cod_ai_request ar ON (am.model_id = ar.model_id)
                WHERE 1";
        
        if (!empty($filter_data['filter_type'])) {
            $sql .= " AND am.model_type = '" . $this->db->escape($filter_data['filter_type']) . "'";
        }
        
        if (!empty($filter_data['filter_provider'])) {
            $sql .= " AND am.provider = '" . $this->db->escape($filter_data['filter_provider']) . "'";
        }
        
        if (!empty($filter_data['filter_status'])) {
            $sql .= " AND am.status = '" . $this->db->escape($filter_data['filter_status']) . "'";
        }
        
        $sql .= " GROUP BY am.model_id";
        $sql .= " ORDER BY am.created_at DESC";
        
        if (isset($filter_data['start']) || isset($filter_data['limit'])) {
            if ($filter_data['start'] < 0) {
                $filter_data['start'] = 0;
            }
            
            if ($filter_data['limit'] < 1) {
                $filter_data['limit'] = 20;
            }
            
            $sql .= " LIMIT " . (int)$filter_data['start'] . "," . (int)$filter_data['limit'];
        }
        
        $query = $this->db->query($sql);
        
        return $query->rows;
    }
}
