<?php
/**
 * المساعد الذكي (AI Assistant / Copilot)
 * 
 * يوفر مساعد ذكي متقدم للمستخدمين مع:
 * - إجابة على الأسئلة حول النظام
 * - مساعدة في تحليل البيانات
 * - اقتراحات ذكية للعمليات
 * - أتمتة المهام الروتينية
 * - تدريب المستخدمين الجدد
 * - حل المشاكل التقنية
 * 
 * @author ERP Team
 * @version 1.0
 * @since 2024
 */

class ModelAiAiAssistant extends Model {
    
    private $conversation_context = [];
    private $user_preferences = [];
    
    /**
     * بدء محادثة مع المساعد الذكي
     */
    public function startConversation($user_message, $context = []) {
        // تحضير السياق
        $this->loadUserPreferences();
        $this->loadConversationHistory();
        
        // تحليل نوع الطلب
        $request_type = $this->analyzeRequestType($user_message);
        
        // إنشاء سجل المحادثة
        $conversation_id = $this->createConversationRecord($user_message, $context);
        
        try {
            // معالجة الطلب حسب النوع
            $response = $this->processRequest($request_type, $user_message, $context);
            
            // حفظ الرد
            $this->saveAssistantResponse($conversation_id, $response);
            
            // تحديث السياق
            $this->updateConversationContext($user_message, $response);
            
            return [
                'conversation_id' => $conversation_id,
                'response' => $response,
                'request_type' => $request_type,
                'suggestions' => $this->generateSuggestions($request_type, $context)
            ];
            
        } catch (Exception $e) {
            $this->saveAssistantResponse($conversation_id, [
                'type' => 'error',
                'message' => 'عذراً، حدث خطأ أثناء معالجة طلبك. يرجى المحاولة مرة أخرى.',
                'error_details' => $e->getMessage()
            ]);
            
            throw $e;
        }
    }
    
    /**
     * تحليل نوع الطلب
     */
    private function analyzeRequestType($message) {
        $message_lower = strtolower($message);
        
        // كلمات مفتاحية للتصنيف
        $keywords = [
            'data_analysis' => ['تحليل', 'إحصائيات', 'تقرير', 'بيانات', 'رسم بياني'],
            'system_help' => ['كيف', 'طريقة', 'شرح', 'مساعدة', 'تعليمات'],
            'automation' => ['أتمتة', 'تلقائي', 'جدولة', 'تكرار', 'روتين'],
            'troubleshooting' => ['مشكلة', 'خطأ', 'لا يعمل', 'عطل', 'إصلاح'],
            'recommendation' => ['اقتراح', 'نصيحة', 'توصية', 'أفضل', 'تحسين'],
            'calculation' => ['حساب', 'احسب', 'كم', 'مجموع', 'متوسط'],
            'search' => ['ابحث', 'أين', 'عرض', 'إظهار', 'قائمة']
        ];
        
        foreach ($keywords as $type => $words) {
            foreach ($words as $word) {
                if (strpos($message_lower, $word) !== false) {
                    return $type;
                }
            }
        }
        
        return 'general_inquiry';
    }
    
    /**
     * معالجة الطلب حسب النوع
     */
    private function processRequest($request_type, $message, $context) {
        switch ($request_type) {
            case 'data_analysis':
                return $this->handleDataAnalysisRequest($message, $context);
                
            case 'system_help':
                return $this->handleSystemHelpRequest($message, $context);
                
            case 'automation':
                return $this->handleAutomationRequest($message, $context);
                
            case 'troubleshooting':
                return $this->handleTroubleshootingRequest($message, $context);
                
            case 'recommendation':
                return $this->handleRecommendationRequest($message, $context);
                
            case 'calculation':
                return $this->handleCalculationRequest($message, $context);
                
            case 'search':
                return $this->handleSearchRequest($message, $context);
                
            default:
                return $this->handleGeneralInquiry($message, $context);
        }
    }
    
    /**
     * معالجة طلبات تحليل البيانات
     */
    private function handleDataAnalysisRequest($message, $context) {
        // استخراج نوع التحليل المطلوب
        $analysis_type = $this->extractAnalysisType($message);
        
        // الحصول على البيانات ذات الصلة
        $data = $this->getRelevantData($analysis_type, $context);
        
        if (empty($data)) {
            return [
                'type' => 'info',
                'message' => 'لم أتمكن من العثور على بيانات كافية لإجراء التحليل المطلوب.',
                'suggestions' => [
                    'تأكد من وجود بيانات في النظام',
                    'حدد الفترة الزمنية للتحليل',
                    'تحقق من صلاحياتك للوصول للبيانات'
                ]
            ];
        }
        
        // تحليل البيانات باستخدام الذكاء الاصطناعي
        $this->load->model('ai/ai_center_management');
        $analysis_result = $this->model_ai_ai_center_management->analyzeData($analysis_type, $data);
        
        return [
            'type' => 'analysis',
            'message' => 'تم إجراء التحليل بنجاح. إليك النتائج:',
            'data' => $analysis_result,
            'charts' => $this->generateCharts($analysis_result),
            'insights' => $this->generateInsights($analysis_result),
            'actions' => $this->suggestActions($analysis_result)
        ];
    }
    
    /**
     * معالجة طلبات المساعدة في النظام
     */
    private function handleSystemHelpRequest($message, $context) {
        // البحث في قاعدة المعرفة
        $help_content = $this->searchKnowledgeBase($message);
        
        if ($help_content) {
            return [
                'type' => 'help',
                'message' => 'وجدت المعلومات التالية التي قد تساعدك:',
                'content' => $help_content,
                'related_topics' => $this->getRelatedTopics($help_content['topic']),
                'video_tutorials' => $this->getVideoTutorials($help_content['topic'])
            ];
        }
        
        // إذا لم توجد معلومات، استخدم الذكاء الاصطناعي
        return $this->generateAiHelp($message, $context);
    }
    
    /**
     * معالجة طلبات الأتمتة
     */
    private function handleAutomationRequest($message, $context) {
        // تحليل المهمة المطلوب أتمتتها
        $task_analysis = $this->analyzeAutomationTask($message);
        
        // البحث عن قوالب سير عمل مناسبة
        $workflow_templates = $this->findWorkflowTemplates($task_analysis);
        
        if (!empty($workflow_templates)) {
            return [
                'type' => 'automation',
                'message' => 'وجدت قوالب سير عمل يمكن أن تساعدك في أتمتة هذه المهمة:',
                'templates' => $workflow_templates,
                'custom_workflow' => $this->suggestCustomWorkflow($task_analysis),
                'setup_guide' => $this->generateSetupGuide($task_analysis)
            ];
        }
        
        // اقتراح إنشاء سير عمل مخصص
        return [
            'type' => 'automation',
            'message' => 'يمكنني مساعدتك في إنشاء سير عمل مخصص لأتمتة هذه المهمة.',
            'workflow_suggestion' => $this->designCustomWorkflow($task_analysis),
            'implementation_steps' => $this->getImplementationSteps($task_analysis)
        ];
    }
    
    /**
     * معالجة طلبات حل المشاكل
     */
    private function handleTroubleshootingRequest($message, $context) {
        // تحليل المشكلة
        $problem_analysis = $this->analyzeProblem($message, $context);
        
        // البحث عن حلول مشابهة
        $similar_issues = $this->findSimilarIssues($problem_analysis);
        
        // تشخيص المشكلة
        $diagnosis = $this->diagnoseProblem($problem_analysis, $context);
        
        return [
            'type' => 'troubleshooting',
            'message' => 'دعني أساعدك في حل هذه المشكلة:',
            'diagnosis' => $diagnosis,
            'solutions' => $this->generateSolutions($diagnosis),
            'similar_cases' => $similar_issues,
            'prevention_tips' => $this->getPreventionTips($diagnosis)
        ];
    }
    
    /**
     * معالجة طلبات التوصيات
     */
    private function handleRecommendationRequest($message, $context) {
        // تحليل السياق للحصول على توصيات مناسبة
        $recommendation_context = $this->analyzeRecommendationContext($message, $context);
        
        // الحصول على بيانات الأداء الحالي
        $performance_data = $this->getCurrentPerformanceData($recommendation_context);
        
        // توليد التوصيات باستخدام الذكاء الاصطناعي
        $this->load->model('ai/ai_center_management');
        $recommendations = $this->model_ai_ai_center_management->optimizeBusinessProcess(
            $recommendation_context['process_type'],
            $performance_data,
            $recommendation_context['goals']
        );
        
        return [
            'type' => 'recommendation',
            'message' => 'بناءً على تحليل البيانات، إليك توصياتي:',
            'recommendations' => $recommendations,
            'impact_analysis' => $this->analyzeImpact($recommendations),
            'implementation_plan' => $this->createImplementationPlan($recommendations),
            'success_metrics' => $this->defineSuccessMetrics($recommendations)
        ];
    }
    
    /**
     * معالجة طلبات الحسابات
     */
    private function handleCalculationRequest($message, $context) {
        // استخراج نوع الحساب المطلوب
        $calculation_type = $this->extractCalculationType($message);
        
        // الحصول على البيانات المطلوبة
        $calculation_data = $this->getCalculationData($calculation_type, $message, $context);
        
        // تنفيذ الحساب
        $result = $this->performCalculation($calculation_type, $calculation_data);
        
        return [
            'type' => 'calculation',
            'message' => 'إليك نتيجة الحساب:',
            'result' => $result,
            'breakdown' => $this->getCalculationBreakdown($calculation_type, $calculation_data, $result),
            'related_metrics' => $this->getRelatedMetrics($calculation_type, $result),
            'export_options' => $this->getExportOptions($result)
        ];
    }
    
    /**
     * معالجة طلبات البحث
     */
    private function handleSearchRequest($message, $context) {
        // استخراج معايير البحث
        $search_criteria = $this->extractSearchCriteria($message);
        
        // تنفيذ البحث في قواعد البيانات المختلفة
        $search_results = $this->performAdvancedSearch($search_criteria);
        
        return [
            'type' => 'search',
            'message' => 'وجدت النتائج التالية:',
            'results' => $search_results,
            'filters' => $this->generateSearchFilters($search_criteria),
            'export_options' => $this->getSearchExportOptions($search_results),
            'related_searches' => $this->suggestRelatedSearches($search_criteria)
        ];
    }
    
    /**
     * معالجة الاستفسارات العامة
     */
    private function handleGeneralInquiry($message, $context) {
        // استخدام الذكاء الاصطناعي للرد العام
        $this->load->model('ai/ai_center_management');
        
        $ai_response = $this->model_ai_ai_center_management->executeAiRequest(
            $this->getGeneralAssistantModel(),
            [
                'message' => $message,
                'context' => $context,
                'user_role' => $this->user->getGroupName(),
                'system_info' => $this->getSystemInfo()
            ]
        );
        
        return [
            'type' => 'general',
            'message' => $ai_response['response'],
            'confidence' => $ai_response['confidence'] ?? 0.8,
            'follow_up_questions' => $this->generateFollowUpQuestions($message, $ai_response),
            'helpful_links' => $this->getHelpfulLinks($message)
        ];
    }
    
    /**
     * توليد اقتراحات ذكية
     */
    private function generateSuggestions($request_type, $context) {
        $suggestions = [];
        
        switch ($request_type) {
            case 'data_analysis':
                $suggestions = [
                    'عرض تحليل مقارن للفترة السابقة',
                    'إنشاء تقرير مفصل',
                    'جدولة تحديث تلقائي للتحليل',
                    'مشاركة النتائج مع الفريق'
                ];
                break;
                
            case 'system_help':
                $suggestions = [
                    'عرض دليل المستخدم الكامل',
                    'مشاهدة فيديو تعليمي',
                    'التواصل مع الدعم الفني',
                    'تصفح الأسئلة الشائعة'
                ];
                break;
                
            case 'automation':
                $suggestions = [
                    'إنشاء سير عمل جديد',
                    'تعديل سير عمل موجود',
                    'جدولة تنفيذ تلقائي',
                    'مراقبة أداء الأتمتة'
                ];
                break;
        }
        
        return $suggestions;
    }
    
    /**
     * تحميل تفضيلات المستخدم
     */
    private function loadUserPreferences() {
        $query = $this->db->query("
            SELECT * FROM cod_user_ai_preferences 
            WHERE user_id = '" . (int)$this->user->getId() . "'
        ");
        
        if ($query->num_rows) {
            $this->user_preferences = json_decode($query->row['preferences'], true);
        } else {
            $this->user_preferences = $this->getDefaultPreferences();
        }
    }
    
    /**
     * تحميل تاريخ المحادثة
     */
    private function loadConversationHistory() {
        $query = $this->db->query("
            SELECT * FROM cod_ai_conversation 
            WHERE user_id = '" . (int)$this->user->getId() . "'
            ORDER BY created_at DESC
            LIMIT 10
        ");
        
        $this->conversation_context = $query->rows;
    }
    
    /**
     * إنشاء سجل المحادثة
     */
    private function createConversationRecord($message, $context) {
        $this->db->query("
            INSERT INTO cod_ai_conversation SET 
            user_id = '" . (int)$this->user->getId() . "',
            user_message = '" . $this->db->escape($message) . "',
            context = '" . $this->db->escape(json_encode($context)) . "',
            session_id = '" . $this->db->escape($this->session->getId()) . "',
            created_at = NOW()
        ");
        
        return $this->db->getLastId();
    }
    
    /**
     * حفظ رد المساعد
     */
    private function saveAssistantResponse($conversation_id, $response) {
        $this->db->query("
            UPDATE cod_ai_conversation SET 
            assistant_response = '" . $this->db->escape(json_encode($response)) . "',
            response_time = NOW()
            WHERE conversation_id = '" . (int)$conversation_id . "'
        ");
    }
    
    /**
     * الحصول على معلومات النظام
     */
    private function getSystemInfo() {
        return [
            'version' => VERSION,
            'modules' => $this->getActiveModules(),
            'user_role' => $this->user->getGroupName(),
            'company_info' => $this->getCompanyInfo(),
            'current_time' => date('Y-m-d H:i:s'),
            'timezone' => date_default_timezone_get()
        ];
    }
    
    /**
     * الحصول على نموذج المساعد العام
     */
    private function getGeneralAssistantModel() {
        $query = $this->db->query("
            SELECT model_id FROM cod_ai_model 
            WHERE model_type = 'general_assistant'
            AND status = 'active'
            ORDER BY created_at DESC
            LIMIT 1
        ");
        
        return $query->num_rows ? $query->row['model_id'] : null;
    }
    
    /**
     * الحصول على الإعدادات الافتراضية
     */
    private function getDefaultPreferences() {
        return [
            'language' => 'ar',
            'response_style' => 'detailed',
            'include_charts' => true,
            'include_suggestions' => true,
            'notification_level' => 'medium'
        ];
    }
}
