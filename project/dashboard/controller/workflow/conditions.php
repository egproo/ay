<?php
/**
 * نظام الشروط المتقدمة - Workflow Conditions
 * Advanced Workflow Conditions Controller (n8n-like)
 *
 * نظام شروط ذكي للتحكم في تدفق العمليات مع منطق متقدم وتكامل AI
 * مطور بمستوى عالمي لتفوق على Odoo وn8n
 *
 * @package    AYM ERP
 * @author     AYM ERP Development Team
 * @copyright  2024 AYM ERP
 * @license    Proprietary
 * @version    1.0.0
 * @link       https://aym-erp.com
 * @since      2024-12-19
 */

class ControllerWorkflowConditions extends Controller {

    /**
     * @var array خطأ في النظام
     */
    private $error = array();

    /**
     * عرض صفحة الشروط الرئيسية
     */
    public function index() {
        $this->load->language('workflow/conditions');

        $this->document->setTitle($this->language->get('heading_title'));

        // التحقق من الصلاحيات
        if (!$this->user->hasPermission('access', 'workflow/conditions')) {
            $this->response->redirect($this->url->link('error/permission', 'user_token=' . $this->session->data['user_token'], true));
        }

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('workflow/conditions', 'user_token=' . $this->session->data['user_token'], true)
        );

        // تحميل الشروط
        $this->load->model('workflow/conditions');

        $filter_data = array(
            'start' => 0,
            'limit' => 20
        );

        $data['conditions'] = $this->model_workflow_conditions->getConditions($filter_data);
        $data['total'] = $this->model_workflow_conditions->getTotalConditions($filter_data);

        // إحصائيات الشروط
        $data['condition_stats'] = array(
            'total_conditions' => $this->model_workflow_conditions->getTotalConditions(),
            'active_conditions' => $this->model_workflow_conditions->getActiveConditions(),
            'evaluations_today' => $this->model_workflow_conditions->getEvaluationsToday(),
            'success_rate' => $this->model_workflow_conditions->getSuccessRate(),
            'most_used_condition' => $this->model_workflow_conditions->getMostUsedCondition(),
            'average_evaluation_time' => $this->model_workflow_conditions->getAverageEvaluationTime()
        );

        // مكتبة الشروط المتقدمة (شبيه بـ n8n)
        $data['condition_library'] = array(
            'basic_conditions' => array(
                'name' => $this->language->get('text_basic_conditions'),
                'description' => $this->language->get('text_basic_conditions_desc'),
                'icon' => 'fa-code-fork',
                'color' => 'primary',
                'conditions' => array(
                    'equals' => array(
                        'name' => $this->language->get('text_equals'),
                        'description' => 'التحقق من تساوي قيمتين',
                        'operator' => '==',
                        'inputs' => array('value1', 'value2'),
                        'examples' => array('{{status}} == "active"', '{{quantity}} == 0', '{{user_role}} == "admin"')
                    ),
                    'not_equals' => array(
                        'name' => $this->language->get('text_not_equals'),
                        'description' => 'التحقق من عدم تساوي قيمتين',
                        'operator' => '!=',
                        'inputs' => array('value1', 'value2'),
                        'examples' => array('{{status}} != "deleted"', '{{price}} != 0', '{{department}} != "HR"')
                    ),
                    'greater_than' => array(
                        'name' => $this->language->get('text_greater_than'),
                        'description' => 'التحقق من أن القيمة الأولى أكبر من الثانية',
                        'operator' => '>',
                        'inputs' => array('value1', 'value2'),
                        'examples' => array('{{quantity}} > 10', '{{price}} > 100', '{{age}} > 18')
                    ),
                    'less_than' => array(
                        'name' => $this->language->get('text_less_than'),
                        'description' => 'التحقق من أن القيمة الأولى أصغر من الثانية',
                        'operator' => '<',
                        'inputs' => array('value1', 'value2'),
                        'examples' => array('{{stock}} < 5', '{{discount}} < 0.5', '{{score}} < 50')
                    ),
                    'contains' => array(
                        'name' => $this->language->get('text_contains'),
                        'description' => 'التحقق من احتواء النص على قيمة معينة',
                        'operator' => 'contains',
                        'inputs' => array('text', 'search_value'),
                        'examples' => array('{{email}} contains "@gmail.com"', '{{name}} contains "Ahmed"', '{{description}} contains "urgent"')
                    ),
                    'is_empty' => array(
                        'name' => $this->language->get('text_is_empty'),
                        'description' => 'التحقق من أن القيمة فارغة',
                        'operator' => 'is_empty',
                        'inputs' => array('value'),
                        'examples' => array('{{notes}} is empty', '{{phone}} is empty', '{{address}} is empty')
                    )
                )
            ),
            'date_time_conditions' => array(
                'name' => $this->language->get('text_date_time_conditions'),
                'description' => $this->language->get('text_date_time_desc'),
                'icon' => 'fa-calendar',
                'color' => 'success',
                'conditions' => array(
                    'date_equals' => array(
                        'name' => $this->language->get('text_date_equals'),
                        'description' => 'التحقق من تساوي التواريخ',
                        'operator' => 'date_equals',
                        'inputs' => array('date1', 'date2'),
                        'examples' => array('{{created_date}} date_equals "2024-12-19"', '{{due_date}} date_equals today', '{{birth_date}} date_equals "1990-01-01"')
                    ),
                    'date_before' => array(
                        'name' => $this->language->get('text_date_before'),
                        'description' => 'التحقق من أن التاريخ الأول قبل الثاني',
                        'operator' => 'date_before',
                        'inputs' => array('date1', 'date2'),
                        'examples' => array('{{due_date}} date_before today', '{{start_date}} date_before "2025-01-01"', '{{created_at}} date_before {{modified_at}}')
                    ),
                    'date_after' => array(
                        'name' => $this->language->get('text_date_after'),
                        'description' => 'التحقق من أن التاريخ الأول بعد الثاني',
                        'operator' => 'date_after',
                        'inputs' => array('date1', 'date2'),
                        'examples' => array('{{expiry_date}} date_after today', '{{end_date}} date_after {{start_date}}', '{{payment_date}} date_after "2024-01-01"')
                    ),
                    'date_between' => array(
                        'name' => $this->language->get('text_date_between'),
                        'description' => 'التحقق من أن التاريخ بين تاريخين',
                        'operator' => 'date_between',
                        'inputs' => array('date', 'start_date', 'end_date'),
                        'examples' => array('{{order_date}} date_between "2024-01-01" and "2024-12-31"', '{{birth_date}} date_between "1980-01-01" and "2000-12-31"')
                    ),
                    'time_between' => array(
                        'name' => $this->language->get('text_time_between'),
                        'description' => 'التحقق من أن الوقت بين وقتين',
                        'operator' => 'time_between',
                        'inputs' => array('time', 'start_time', 'end_time'),
                        'examples' => array('{{current_time}} time_between "09:00" and "17:00"', '{{login_time}} time_between "08:00" and "18:00"')
                    )
                )
            ),
            'logical_conditions' => array(
                'name' => $this->language->get('text_logical_conditions'),
                'description' => $this->language->get('text_logical_desc'),
                'icon' => 'fa-sitemap',
                'color' => 'warning',
                'conditions' => array(
                    'and_condition' => array(
                        'name' => $this->language->get('text_and_condition'),
                        'description' => 'جميع الشروط يجب أن تكون صحيحة',
                        'operator' => 'AND',
                        'inputs' => array('condition1', 'condition2', '...'),
                        'examples' => array('{{status}} == "active" AND {{quantity}} > 0', '{{age}} >= 18 AND {{country}} == "Egypt"')
                    ),
                    'or_condition' => array(
                        'name' => $this->language->get('text_or_condition'),
                        'description' => 'شرط واحد على الأقل يجب أن يكون صحيحاً',
                        'operator' => 'OR',
                        'inputs' => array('condition1', 'condition2', '...'),
                        'examples' => array('{{status}} == "pending" OR {{status}} == "processing"', '{{priority}} == "high" OR {{urgent}} == true')
                    ),
                    'not_condition' => array(
                        'name' => $this->language->get('text_not_condition'),
                        'description' => 'عكس الشرط',
                        'operator' => 'NOT',
                        'inputs' => array('condition'),
                        'examples' => array('NOT ({{status}} == "deleted")', 'NOT ({{quantity}} == 0)')
                    ),
                    'if_then_else' => array(
                        'name' => $this->language->get('text_if_then_else'),
                        'description' => 'شرط مع نتيجتين مختلفتين',
                        'operator' => 'IF_THEN_ELSE',
                        'inputs' => array('condition', 'true_result', 'false_result'),
                        'examples' => array('IF {{quantity}} > 0 THEN "available" ELSE "out_of_stock"', 'IF {{age}} >= 18 THEN "adult" ELSE "minor"')
                    )
                )
            ),
            'data_validation' => array(
                'name' => $this->language->get('text_data_validation'),
                'description' => $this->language->get('text_data_validation_desc'),
                'icon' => 'fa-check-circle',
                'color' => 'info',
                'conditions' => array(
                    'is_numeric' => array(
                        'name' => $this->language->get('text_is_numeric'),
                        'description' => 'التحقق من أن القيمة رقمية',
                        'operator' => 'is_numeric',
                        'inputs' => array('value'),
                        'examples' => array('{{price}} is_numeric', '{{quantity}} is_numeric', '{{age}} is_numeric')
                    ),
                    'is_email' => array(
                        'name' => $this->language->get('text_is_email'),
                        'description' => 'التحقق من صحة البريد الإلكتروني',
                        'operator' => 'is_email',
                        'inputs' => array('email'),
                        'examples' => array('{{customer_email}} is_email', '{{contact_email}} is_email')
                    ),
                    'is_phone' => array(
                        'name' => $this->language->get('text_is_phone'),
                        'description' => 'التحقق من صحة رقم الهاتف',
                        'operator' => 'is_phone',
                        'inputs' => array('phone'),
                        'examples' => array('{{customer_phone}} is_phone', '{{contact_number}} is_phone')
                    ),
                    'regex_match' => array(
                        'name' => $this->language->get('text_regex_match'),
                        'description' => 'التحقق من مطابقة نمط معين',
                        'operator' => 'regex_match',
                        'inputs' => array('value', 'pattern'),
                        'examples' => array('{{product_code}} regex_match "^[A-Z]{3}[0-9]{3}$"', '{{license_plate}} regex_match "^[0-9]{3}[A-Z]{3}$"')
                    ),
                    'length_between' => array(
                        'name' => $this->language->get('text_length_between'),
                        'description' => 'التحقق من طول النص',
                        'operator' => 'length_between',
                        'inputs' => array('text', 'min_length', 'max_length'),
                        'examples' => array('{{password}} length_between 8 and 20', '{{username}} length_between 3 and 15')
                    )
                )
            ),
            'ai_conditions' => array(
                'name' => $this->language->get('text_ai_conditions'),
                'description' => $this->language->get('text_ai_conditions_desc'),
                'icon' => 'fa-brain',
                'color' => 'secondary',
                'conditions' => array(
                    'ai_sentiment' => array(
                        'name' => $this->language->get('text_ai_sentiment'),
                        'description' => 'تحليل المشاعر بالذكاء الاصطناعي',
                        'operator' => 'ai_sentiment',
                        'inputs' => array('text', 'sentiment_type'),
                        'examples' => array('{{customer_feedback}} ai_sentiment "positive"', '{{review_text}} ai_sentiment "negative"')
                    ),
                    'ai_classification' => array(
                        'name' => $this->language->get('text_ai_classification'),
                        'description' => 'تصنيف البيانات بالذكاء الاصطناعي',
                        'operator' => 'ai_classification',
                        'inputs' => array('data', 'model', 'category'),
                        'examples' => array('{{customer_data}} ai_classification "customer_segment" == "VIP"', '{{product_description}} ai_classification "category" == "electronics"')
                    ),
                    'ai_prediction' => array(
                        'name' => $this->language->get('text_ai_prediction'),
                        'description' => 'التنبؤ بالذكاء الاصطناعي',
                        'operator' => 'ai_prediction',
                        'inputs' => array('data', 'model', 'threshold'),
                        'examples' => array('{{sales_data}} ai_prediction "demand_forecast" > 100', '{{customer_behavior}} ai_prediction "churn_risk" > 0.7')
                    ),
                    'ai_anomaly' => array(
                        'name' => $this->language->get('text_ai_anomaly'),
                        'description' => 'كشف الشذوذ بالذكاء الاصطناعي',
                        'operator' => 'ai_anomaly',
                        'inputs' => array('data', 'model', 'threshold'),
                        'examples' => array('{{transaction_data}} ai_anomaly "fraud_detection" > 0.8', '{{system_metrics}} ai_anomaly "performance_issue" > 0.6')
                    )
                )
            ),
            'business_rules' => array(
                'name' => $this->language->get('text_business_rules'),
                'description' => $this->language->get('text_business_rules_desc'),
                'icon' => 'fa-briefcase',
                'color' => 'dark',
                'conditions' => array(
                    'inventory_level' => array(
                        'name' => $this->language->get('text_inventory_level'),
                        'description' => 'التحقق من مستوى المخزون',
                        'operator' => 'inventory_level',
                        'inputs' => array('product_id', 'operator', 'threshold'),
                        'examples' => array('{{product_id}} inventory_level < 10', '{{item_code}} inventory_level == 0')
                    ),
                    'customer_credit' => array(
                        'name' => $this->language->get('text_customer_credit'),
                        'description' => 'التحقق من الائتمان المتاح للعميل',
                        'operator' => 'customer_credit',
                        'inputs' => array('customer_id', 'operator', 'amount'),
                        'examples' => array('{{customer_id}} customer_credit > {{order_total}}', '{{client_id}} customer_credit < 1000')
                    ),
                    'approval_required' => array(
                        'name' => $this->language->get('text_approval_required'),
                        'description' => 'التحقق من الحاجة للموافقة',
                        'operator' => 'approval_required',
                        'inputs' => array('request_type', 'amount', 'user_role'),
                        'examples' => array('{{request_type}} approval_required for {{amount}} by {{user_role}}', '"purchase_order" approval_required for amount > 5000')
                    ),
                    'working_hours' => array(
                        'name' => $this->language->get('text_working_hours'),
                        'description' => 'التحقق من ساعات العمل',
                        'operator' => 'working_hours',
                        'inputs' => array('current_time', 'timezone'),
                        'examples' => array('{{current_time}} working_hours "Asia/Cairo"', 'now working_hours "UTC"')
                    )
                )
            )
        );

        // الشروط الأكثر استخداماً
        $data['popular_conditions'] = $this->model_workflow_conditions->getPopularConditions(10);

        // الشروط الحديثة
        $data['recent_conditions'] = $this->model_workflow_conditions->getRecentConditions(10);

        // تحليل الأداء
        $data['performance_metrics'] = array(
            'evaluation_trends' => $this->model_workflow_conditions->getEvaluationTrends(30),
            'success_rate_by_type' => $this->model_workflow_conditions->getSuccessRateByType(),
            'average_evaluation_time' => $this->model_workflow_conditions->getAverageEvaluationTimeByType(),
            'error_analysis' => $this->model_workflow_conditions->getErrorAnalysis()
        );

        // الروابط
        $data['add'] = $this->url->link('workflow/conditions/add', 'user_token=' . $this->session->data['user_token'], true);
        $data['condition_builder'] = $this->url->link('workflow/conditions/builder', 'user_token=' . $this->session->data['user_token'], true);
        $data['test_condition'] = $this->url->link('workflow/conditions/test', 'user_token=' . $this->session->data['user_token'], true);
        $data['import_export'] = $this->url->link('workflow/conditions/import_export', 'user_token=' . $this->session->data['user_token'], true);
        $data['monitoring'] = $this->url->link('workflow/conditions/monitoring', 'user_token=' . $this->session->data['user_token'], true);
        $data['settings'] = $this->url->link('workflow/conditions/settings', 'user_token=' . $this->session->data['user_token'], true);

        // الرسائل
        if (isset($this->session->data['success'])) {
            $data['success'] = $this->session->data['success'];
            unset($this->session->data['success']);
        } else {
            $data['success'] = '';
        }

        // التوكن
        $data['user_token'] = $this->session->data['user_token'];

        // عرض الصفحة
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('workflow/conditions', $data));
    }

    /**
     * منشئ الشروط المرئي (Condition Builder)
     */
    public function builder() {
        $this->load->language('workflow/conditions');

        $this->document->setTitle($this->language->get('text_condition_builder'));

        // التحقق من الصلاحيات
        if (!$this->user->hasPermission('modify', 'workflow/conditions')) {
            $this->response->redirect($this->url->link('error/permission', 'user_token=' . $this->session->data['user_token'], true));
        }

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('workflow/conditions', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_condition_builder'),
            'href' => $this->url->link('workflow/conditions/builder', 'user_token=' . $this->session->data['user_token'], true)
        );

        // إعدادات منشئ الشروط
        $data['builder_config'] = array(
            'visual_editor' => true,
            'code_editor' => true,
            'syntax_highlighting' => true,
            'auto_complete' => true,
            'real_time_validation' => true,
            'test_mode' => true
        );

        // قوالب الشروط الجاهزة
        $data['condition_templates'] = array(
            'simple_comparison' => array(
                'name' => 'مقارنة بسيطة',
                'description' => 'مقارنة قيمة بقيمة أخرى',
                'category' => 'basic',
                'template' => '{{field_name}} {{operator}} {{value}}',
                'example' => '{{quantity}} > 10'
            ),
            'date_range_check' => array(
                'name' => 'فحص نطاق التاريخ',
                'description' => 'التحقق من وقوع التاريخ في نطاق معين',
                'category' => 'date',
                'template' => '{{date_field}} BETWEEN {{start_date}} AND {{end_date}}',
                'example' => '{{order_date}} BETWEEN "2024-01-01" AND "2024-12-31"'
            ),
            'multi_condition' => array(
                'name' => 'شروط متعددة',
                'description' => 'دمج عدة شروط بمنطق AND/OR',
                'category' => 'logical',
                'template' => '({{condition1}}) {{logical_operator}} ({{condition2}})',
                'example' => '({{status}} == "active") AND ({{quantity}} > 0)'
            ),
            'ai_based_condition' => array(
                'name' => 'شرط ذكي',
                'description' => 'شرط يعتمد على الذكاء الاصطناعي',
                'category' => 'ai',
                'template' => '{{data}} AI_{{function}} {{model}} {{operator}} {{threshold}}',
                'example' => '{{customer_data}} AI_PREDICT churn_model > 0.7'
            )
        );

        // العوامل المتاحة
        $data['available_operators'] = array(
            'comparison' => array(
                '==' => 'يساوي',
                '!=' => 'لا يساوي',
                '>' => 'أكبر من',
                '<' => 'أصغر من',
                '>=' => 'أكبر من أو يساوي',
                '<=' => 'أصغر من أو يساوي'
            ),
            'text' => array(
                'contains' => 'يحتوي على',
                'starts_with' => 'يبدأ بـ',
                'ends_with' => 'ينتهي بـ',
                'regex' => 'يطابق النمط',
                'is_empty' => 'فارغ',
                'is_not_empty' => 'غير فارغ'
            ),
            'logical' => array(
                'AND' => 'و',
                'OR' => 'أو',
                'NOT' => 'ليس',
                'XOR' => 'أو الحصري'
            ),
            'date' => array(
                'date_equals' => 'التاريخ يساوي',
                'date_before' => 'التاريخ قبل',
                'date_after' => 'التاريخ بعد',
                'date_between' => 'التاريخ بين',
                'is_today' => 'اليوم',
                'is_weekend' => 'نهاية الأسبوع'
            ),
            'ai' => array(
                'ai_predict' => 'توقع ذكي',
                'ai_classify' => 'تصنيف ذكي',
                'ai_sentiment' => 'تحليل المشاعر',
                'ai_anomaly' => 'كشف الشذوذ'
            )
        );

        // متغيرات النظام المتاحة
        $data['system_variables'] = array(
            'user' => array(
                'user_id' => 'معرف المستخدم',
                'username' => 'اسم المستخدم',
                'email' => 'بريد المستخدم',
                'role' => 'دور المستخدم',
                'department' => 'قسم المستخدم'
            ),
            'system' => array(
                'current_date' => 'التاريخ الحالي',
                'current_time' => 'الوقت الحالي',
                'current_datetime' => 'التاريخ والوقت الحالي',
                'system_timezone' => 'المنطقة الزمنية',
                'working_hours' => 'ساعات العمل'
            ),
            'workflow' => array(
                'trigger_data' => 'بيانات المحفز',
                'previous_result' => 'نتيجة الخطوة السابقة',
                'workflow_id' => 'معرف سير العمل',
                'execution_id' => 'معرف التنفيذ'
            ),
            'business' => array(
                'company_settings' => 'إعدادات الشركة',
                'fiscal_year' => 'السنة المالية',
                'currency' => 'العملة',
                'tax_rate' => 'معدل الضريبة'
            )
        );

        // دوال مساعدة
        $data['helper_functions'] = array(
            'date_functions' => array(
                'TODAY()' => 'التاريخ الحالي',
                'NOW()' => 'التاريخ والوقت الحالي',
                'DATE_ADD(date, days)' => 'إضافة أيام للتاريخ',
                'DATE_DIFF(date1, date2)' => 'الفرق بين تاريخين',
                'WEEKDAY(date)' => 'يوم الأسبوع',
                'MONTH(date)' => 'الشهر',
                'YEAR(date)' => 'السنة'
            ),
            'text_functions' => array(
                'UPPER(text)' => 'تحويل لأحرف كبيرة',
                'LOWER(text)' => 'تحويل لأحرف صغيرة',
                'LENGTH(text)' => 'طول النص',
                'TRIM(text)' => 'إزالة المسافات',
                'SUBSTRING(text, start, length)' => 'جزء من النص',
                'REPLACE(text, old, new)' => 'استبدال النص'
            ),
            'math_functions' => array(
                'ABS(number)' => 'القيمة المطلقة',
                'ROUND(number, decimals)' => 'التقريب',
                'MAX(num1, num2)' => 'الأكبر',
                'MIN(num1, num2)' => 'الأصغر',
                'SUM(array)' => 'المجموع',
                'AVG(array)' => 'المتوسط'
            ),
            'ai_functions' => array(
                'AI_PREDICT(data, model)' => 'توقع ذكي',
                'AI_CLASSIFY(data, model)' => 'تصنيف ذكي',
                'AI_SENTIMENT(text)' => 'تحليل المشاعر',
                'AI_ANOMALY(data, threshold)' => 'كشف الشذوذ'
            )
        );

        // الروابط
        $data['save_condition'] = $this->url->link('workflow/conditions/save', 'user_token=' . $this->session->data['user_token'], true);
        $data['test_condition'] = $this->url->link('workflow/conditions/test', 'user_token=' . $this->session->data['user_token'], true);
        $data['validate_syntax'] = $this->url->link('workflow/conditions/validate', 'user_token=' . $this->session->data['user_token'], true);
        $data['back'] = $this->url->link('workflow/conditions', 'user_token=' . $this->session->data['user_token'], true);

        // التوكن
        $data['user_token'] = $this->session->data['user_token'];

        // عرض الصفحة
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('workflow/conditions_builder', $data));
    }

    /**
     * تقييم شرط (Evaluate Condition)
     */
    public function evaluate() {
        $this->load->language('workflow/conditions');

        $json = array();

        if (!$this->user->hasPermission('modify', 'workflow/conditions')) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            if (($this->request->server['REQUEST_METHOD'] == 'POST') && isset($this->request->post['condition'])) {
                $this->load->model('workflow/conditions');

                $condition = $this->request->post['condition'];
                $context_data = $this->request->post['context_data'] ?? array();

                try {
                    // تقييم الشرط
                    $result = $this->evaluateCondition($condition, $context_data);

                    // تسجيل التقييم
                    $this->logConditionEvaluation('success', $condition, $context_data, $result);

                    $json['success'] = true;
                    $json['result'] = $result;
                    $json['evaluation_time'] = $result['evaluation_time'] ?? 0;
                    $json['message'] = $this->language->get('text_condition_evaluated_successfully');
                } catch (Exception $e) {
                    // تسجيل الخطأ
                    $this->logConditionEvaluation('error', $condition, $context_data, array('error' => $e->getMessage()));

                    $json['error'] = $this->language->get('error_condition_evaluation_failed');
                    $json['details'] = $e->getMessage();
                }
            } else {
                $json['error'] = $this->language->get('error_condition_required');
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    /**
     * اختبار شرط
     */
    public function test() {
        $this->load->language('workflow/conditions');

        $json = array();

        if (!$this->user->hasPermission('modify', 'workflow/conditions')) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            if (($this->request->server['REQUEST_METHOD'] == 'POST') && isset($this->request->post['condition'])) {
                $this->load->model('workflow/conditions');

                $condition = $this->request->post['condition'];
                $test_data = $this->request->post['test_data'] ?? array();

                // تشغيل الشرط في وضع الاختبار
                $test_result = $this->model_workflow_conditions->testCondition($condition, $test_data);

                if ($test_result['success']) {
                    // تسجيل نتيجة الاختبار
                    $this->logConditionEvaluation('test', $condition, $test_data, $test_result);

                    $json['success'] = true;
                    $json['result'] = $test_result;
                    $json['message'] = $this->language->get('text_test_successful');
                    $json['evaluation_time'] = $test_result['evaluation_time'] ?? 0;
                    $json['steps'] = $test_result['evaluation_steps'] ?? array();
                } else {
                    $json['error'] = $test_result['error'];
                    $json['details'] = $test_result['details'] ?? '';
                    $json['syntax_errors'] = $test_result['syntax_errors'] ?? array();
                }
            } else {
                $json['error'] = $this->language->get('error_test_data_required');
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    /**
     * تقييم شرط محدد
     */
    private function evaluateCondition($condition, $context_data) {
        $start_time = microtime(true);

        // استبدال المتغيرات
        $processed_condition = $this->replaceVariables($condition, $context_data);

        // تحليل الشرط
        $parsed_condition = $this->parseCondition($processed_condition);

        // تنفيذ التقييم
        $result = $this->executeConditionEvaluation($parsed_condition, $context_data);

        $end_time = microtime(true);
        $evaluation_time = ($end_time - $start_time) * 1000; // بالميلي ثانية

        return array(
            'condition_result' => $result,
            'evaluation_time' => round($evaluation_time, 2),
            'processed_condition' => $processed_condition,
            'evaluation_steps' => $this->getEvaluationSteps($parsed_condition)
        );
    }

    /**
     * تحليل الشرط إلى مكونات
     */
    private function parseCondition($condition) {
        // إزالة المسافات الزائدة
        $condition = trim($condition);

        // التحقق من الأقواس
        if (!$this->validateParentheses($condition)) {
            throw new Exception('خطأ في الأقواس: الأقواس غير متوازنة');
        }

        // تحليل العوامل المنطقية (AND, OR, NOT)
        if (preg_match('/\b(AND|OR)\b/i', $condition)) {
            return $this->parseLogicalCondition($condition);
        }

        // تحليل الشروط البسيطة
        return $this->parseSimpleCondition($condition);
    }

    /**
     * تحليل الشروط المنطقية
     */
    private function parseLogicalCondition($condition) {
        // البحث عن العوامل المنطقية
        $operators = array('AND', 'OR');

        foreach ($operators as $operator) {
            $pattern = '/\b' . $operator . '\b/i';
            if (preg_match($pattern, $condition)) {
                $parts = preg_split($pattern, $condition, 2);
                if (count($parts) == 2) {
                    return array(
                        'type' => 'logical',
                        'operator' => strtoupper($operator),
                        'left' => $this->parseCondition(trim($parts[0])),
                        'right' => $this->parseCondition(trim($parts[1]))
                    );
                }
            }
        }

        throw new Exception('خطأ في تحليل الشرط المنطقي: ' . $condition);
    }

    /**
     * تحليل الشروط البسيطة
     */
    private function parseSimpleCondition($condition) {
        // إزالة الأقواس الخارجية إذا وجدت
        $condition = preg_replace('/^\s*\(\s*(.*)\s*\)\s*$/', '$1', $condition);

        // البحث عن العوامل
        $operators = array('>=', '<=', '!=', '==', '>', '<', 'contains', 'starts_with', 'ends_with', 'is_empty', 'regex');

        foreach ($operators as $operator) {
            $pattern = '/(.+?)\s*' . preg_quote($operator, '/') . '\s*(.+)/i';
            if (preg_match($pattern, $condition, $matches)) {
                return array(
                    'type' => 'simple',
                    'operator' => $operator,
                    'left' => trim($matches[1]),
                    'right' => isset($matches[2]) ? trim($matches[2]) : null
                );
            }
        }

        // التحقق من الشروط الخاصة
        if (preg_match('/(.+?)\s+(is_empty|is_not_empty)/i', $condition, $matches)) {
            return array(
                'type' => 'special',
                'operator' => strtolower($matches[2]),
                'operand' => trim($matches[1])
            );
        }

        // التحقق من دوال AI
        if (preg_match('/(.+?)\s+(ai_predict|ai_classify|ai_sentiment|ai_anomaly)\s+(.+?)\s*(>|<|>=|<=|==|!=)\s*(.+)/i', $condition, $matches)) {
            return array(
                'type' => 'ai',
                'data' => trim($matches[1]),
                'function' => strtolower($matches[2]),
                'model' => trim($matches[3]),
                'operator' => $matches[4],
                'threshold' => trim($matches[5])
            );
        }

        throw new Exception('خطأ في تحليل الشرط: ' . $condition);
    }

    /**
     * تنفيذ تقييم الشرط
     */
    private function executeConditionEvaluation($parsed_condition, $context_data) {
        switch ($parsed_condition['type']) {
            case 'logical':
                return $this->evaluateLogicalCondition($parsed_condition, $context_data);

            case 'simple':
                return $this->evaluateSimpleCondition($parsed_condition, $context_data);

            case 'special':
                return $this->evaluateSpecialCondition($parsed_condition, $context_data);

            case 'ai':
                return $this->evaluateAICondition($parsed_condition, $context_data);

            default:
                throw new Exception('نوع شرط غير مدعوم: ' . $parsed_condition['type']);
        }
    }

    /**
     * تقييم الشروط المنطقية
     */
    private function evaluateLogicalCondition($condition, $context_data) {
        $left_result = $this->executeConditionEvaluation($condition['left'], $context_data);
        $right_result = $this->executeConditionEvaluation($condition['right'], $context_data);

        switch ($condition['operator']) {
            case 'AND':
                return $left_result && $right_result;

            case 'OR':
                return $left_result || $right_result;

            default:
                throw new Exception('عامل منطقي غير مدعوم: ' . $condition['operator']);
        }
    }

    /**
     * تقييم الشروط البسيطة
     */
    private function evaluateSimpleCondition($condition, $context_data) {
        $left_value = $this->getValue($condition['left'], $context_data);
        $right_value = $this->getValue($condition['right'], $context_data);

        switch ($condition['operator']) {
            case '==':
                return $left_value == $right_value;

            case '!=':
                return $left_value != $right_value;

            case '>':
                return $this->compareValues($left_value, $right_value) > 0;

            case '<':
                return $this->compareValues($left_value, $right_value) < 0;

            case '>=':
                return $this->compareValues($left_value, $right_value) >= 0;

            case '<=':
                return $this->compareValues($left_value, $right_value) <= 0;

            case 'contains':
                return strpos(strtolower($left_value), strtolower($right_value)) !== false;

            case 'starts_with':
                return strpos(strtolower($left_value), strtolower($right_value)) === 0;

            case 'ends_with':
                return substr(strtolower($left_value), -strlen($right_value)) === strtolower($right_value);

            case 'regex':
                return preg_match('/' . $right_value . '/i', $left_value);

            default:
                throw new Exception('عامل مقارنة غير مدعوم: ' . $condition['operator']);
        }
    }

    /**
     * تقييم الشروط الخاصة
     */
    private function evaluateSpecialCondition($condition, $context_data) {
        $value = $this->getValue($condition['operand'], $context_data);

        switch ($condition['operator']) {
            case 'is_empty':
                return empty($value);

            case 'is_not_empty':
                return !empty($value);

            default:
                throw new Exception('عامل خاص غير مدعوم: ' . $condition['operator']);
        }
    }

    /**
     * تقييم شروط AI
     */
    private function evaluateAICondition($condition, $context_data) {
        $this->load->model('ai/analysis');

        $data = $this->getValue($condition['data'], $context_data);
        $model = $condition['model'];
        $threshold = $this->getValue($condition['threshold'], $context_data);

        switch ($condition['function']) {
            case 'ai_predict':
                $result = $this->model_ai_analysis->predict($data, $model);
                return $this->compareValues($result['prediction'], $threshold, $condition['operator']);

            case 'ai_classify':
                $result = $this->model_ai_analysis->classify($data, $model);
                return $this->compareValues($result['confidence'], $threshold, $condition['operator']);

            case 'ai_sentiment':
                $result = $this->model_ai_analysis->analyzeSentiment($data);
                return $this->compareValues($result['score'], $threshold, $condition['operator']);

            case 'ai_anomaly':
                $result = $this->model_ai_analysis->detectAnomaly($data, $model);
                return $this->compareValues($result['anomaly_score'], $threshold, $condition['operator']);

            default:
                throw new Exception('دالة AI غير مدعومة: ' . $condition['function']);
        }
    }

    /**
     * الحصول على قيمة متغير
     */
    private function getValue($expression, $context_data) {
        // إزالة علامات الاقتباس
        $expression = trim($expression, '"\'');

        // التحقق من المتغيرات
        if (preg_match('/^\{\{(.+)\}\}$/', $expression, $matches)) {
            $variable_name = $matches[1];
            return $context_data[$variable_name] ?? null;
        }

        // التحقق من الأرقام
        if (is_numeric($expression)) {
            return is_float($expression + 0) ? (float)$expression : (int)$expression;
        }

        // التحقق من القيم المنطقية
        if (strtolower($expression) === 'true') {
            return true;
        }
        if (strtolower($expression) === 'false') {
            return false;
        }

        // إرجاع النص كما هو
        return $expression;
    }

    /**
     * مقارنة القيم
     */
    private function compareValues($value1, $value2, $operator = null) {
        // تحويل القيم للمقارنة
        if (is_numeric($value1) && is_numeric($value2)) {
            $value1 = (float)$value1;
            $value2 = (float)$value2;
        }

        if ($operator) {
            switch ($operator) {
                case '>': return $value1 > $value2;
                case '<': return $value1 < $value2;
                case '>=': return $value1 >= $value2;
                case '<=': return $value1 <= $value2;
                case '==': return $value1 == $value2;
                case '!=': return $value1 != $value2;
            }
        }

        // مقارنة عادية للترتيب
        if ($value1 == $value2) return 0;
        return $value1 > $value2 ? 1 : -1;
    }

    /**
     * استبدال المتغيرات
     */
    private function replaceVariables($condition, $context_data) {
        // متغيرات النظام
        $condition = str_replace('{{current_date}}', date('Y-m-d'), $condition);
        $condition = str_replace('{{current_time}}', date('H:i:s'), $condition);
        $condition = str_replace('{{current_datetime}}', date('Y-m-d H:i:s'), $condition);
        $condition = str_replace('{{user_id}}', $this->user->getId(), $condition);

        // متغيرات السياق
        foreach ($context_data as $key => $value) {
            if (is_string($value) || is_numeric($value)) {
                $condition = str_replace('{{' . $key . '}}', $value, $condition);
            }
        }

        return $condition;
    }

    /**
     * التحقق من توازن الأقواس
     */
    private function validateParentheses($condition) {
        $count = 0;
        $length = strlen($condition);

        for ($i = 0; $i < $length; $i++) {
            if ($condition[$i] == '(') {
                $count++;
            } elseif ($condition[$i] == ')') {
                $count--;
                if ($count < 0) {
                    return false;
                }
            }
        }

        return $count == 0;
    }

    /**
     * الحصول على خطوات التقييم
     */
    private function getEvaluationSteps($parsed_condition) {
        // إرجاع خطوات التقييم للمساعدة في التصحيح
        return array(
            'condition_type' => $parsed_condition['type'],
            'parsed_structure' => $parsed_condition
        );
    }

    /**
     * تسجيل تقييم الشرط
     */
    private function logConditionEvaluation($status, $condition, $context_data, $result) {
        $this->load->model('logging/user_activity');

        $activity_data = array(
            'action_type' => 'condition_' . $status,
            'module' => 'workflow/conditions',
            'description' => 'تم ' . $status . ' تقييم الشرط',
            'reference_type' => 'workflow_condition',
            'details' => json_encode(array(
                'condition' => $condition,
                'context_data' => $context_data,
                'result' => $result
            ))
        );

        $this->model_logging_user_activity->addActivity($activity_data);
    }
}